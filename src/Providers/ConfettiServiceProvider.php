<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Providers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Simtabi\Laranail\Confetti\Commands\DemoCommand;
use Simtabi\Laranail\Confetti\Commands\DoctorCommand;
use Simtabi\Laranail\Confetti\Commands\InstallCommand;
use Simtabi\Laranail\Confetti\Confetti;
use Simtabi\Laranail\Confetti\Enums\AssetMode;
use Simtabi\Laranail\Confetti\Http\Middleware\InjectConfetti;
use Simtabi\Laranail\Confetti\Integrations\Filament\Providers\FilamentServiceProvider;
use Simtabi\Laranail\Confetti\Payload\PendingBursts;
use Simtabi\Laranail\Confetti\Presets\PresetRegistry;
use Simtabi\Laranail\Confetti\Support\Assets;
use Simtabi\Laranail\Confetti\Support\BootConfig;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\Support\EffectRegistry;
use Simtabi\Laranail\Confetti\Transport\TransportManager;
use Simtabi\Laranail\Confetti\View\ConfettiTags;
use Simtabi\Laranail\Confetti\View\ScriptTagBuilder;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

/**
 * Registers the package.
 *
 * The provider lives in `src/Providers/` deliberately. The base class resolves
 * the package root by walking up from the provider's own file and stripping a
 * trailing `/Providers` and `/src`, which is how it finds `config/`,
 * `resources/` and `routes/`.
 */
final class ConfettiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/confetti')
            ->hasConfigFile()
            ->hasViews('laranail-confetti')
            ->hasAssets()
            ->hasBladeComponentNamespace(
                'Simtabi\\Laranail\\Confetti\\View\\Components',
                'laranail-confetti',
            )
            ->hasCommands([
                InstallCommand::class,
                DemoCommand::class,
                DoctorCommand::class,
            ])
            ->hasAboutSection('Confetti', fn (): array => $this->aboutSection());
    }

    /**
     * The `php artisan about` summary.
     *
     * The three settings that decide whether confetti appears at all, and
     * whether it is cheap, which is what someone reading `about` wants to
     * know. `laranail::confetti.doctor` is the detailed version.
     *
     * @return array<string, string>
     */
    private function aboutSection(): array
    {
        $config = $this->app->make(ConfettiConfig::class);
        $assets = $this->app->make(Assets::class);

        return [
            'Enabled' => $config->enabled ? 'yes' : 'no',
            'Transport' => $config->transport->value,
            'Asset delivery' => $config->assetMode->value,
            'Bundle' => $assets->exists() ? $assets->hash() : 'NOT BUILT',
            'Preset expansion' => $config->expansion->value,
            'Reduced motion' => $config->reducedMotion->value,
            'Auto-inject' => $config->injectValue('auto', false) ? 'yes' : 'no',
        ];
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ConfettiConfig::class, fn (): ConfettiConfig => ConfettiConfig::fromArray(
            (array) $this->app->make(Repository::class)->get('laranail.confetti', []),
        ));

        $this->app->singleton(Assets::class, static fn (): Assets => Assets::default());

        $this->app->singleton(PresetRegistry::class, static fn (): PresetRegistry => new PresetRegistry);

        // Scoped, not a singleton. Under Octane a singleton outlives the
        // request, and this holds the payloads fired during one, so sharing it
        // between visitors would replay one person's confetti at the next.
        $this->app->scoped(PendingBursts::class, static fn (): PendingBursts => new PendingBursts);

        $this->app->singleton(TransportManager::class, fn (): TransportManager => new TransportManager(
            container: $this->app,
            config: $this->app->make(ConfettiConfig::class),
            events: $this->app->bound(Dispatcher::class) ? $this->app->make(Dispatcher::class) : null,
        ));

        $this->app->singleton(EffectRegistry::class, fn (): EffectRegistry => new EffectRegistry(
            $this->app->make(ConfettiConfig::class)->effects,
        ));

        $this->app->singleton(Confetti::class, fn (): Confetti => new Confetti(
            container: $this->app,
            config: $this->app->make(ConfettiConfig::class),
            presets: $this->app->make(PresetRegistry::class),
            transports: $this->app->make(TransportManager::class),
            effects: $this->app->make(EffectRegistry::class),
            events: $this->app->bound(Dispatcher::class) ? $this->app->make(Dispatcher::class) : null,
        ));

        $this->app->bind(BootConfig::class, fn (): BootConfig => new BootConfig(
            config: $this->app->make(ConfettiConfig::class),
            container: $this->app,
        ));

        $this->app->bind(ScriptTagBuilder::class, fn (): ScriptTagBuilder => new ScriptTagBuilder(
            config: $this->app->make(ConfettiConfig::class),
            assets: $this->app->make(Assets::class),
            container: $this->app,
        ));

        // One renderer, shared by the Blade component, the middleware and both
        // Filament entry points, so a panel and a plain page cannot drift apart.
        $this->app->bind(ConfettiTags::class, fn (): ConfettiTags => new ConfettiTags(
            config: $this->app->make(ConfettiConfig::class),
            boot: $this->app->make(BootConfig::class),
            tag: $this->app->make(ScriptTagBuilder::class),
            events: $this->app->bound(Dispatcher::class) ? $this->app->make(Dispatcher::class) : null,
        ));

        $this->app->alias(Confetti::class, 'laranail.confetti');

        // Registered unconditionally; it gates itself on Filament being
        // installed and the integration being enabled.
        $this->app->register(FilamentServiceProvider::class);
    }

    public function packageBooted(): void
    {
        $config = $this->app->make(ConfettiConfig::class);

        $this->registerAssetRoute($config);
        $this->registerMiddleware($config);
    }

    /**
     * The asset route exists only in `route` mode.
     *
     * Registering it unconditionally would leave a live endpoint serving the
     * bundle even for an application that has deliberately switched delivery
     * elsewhere.
     */
    private function registerAssetRoute(ConfettiConfig $config): void
    {
        if ($config->assetMode !== AssetMode::Route || $this->app->routesAreCached()) {
            return;
        }

        $this->loadRoutesFrom($this->package->basePath('/routes/assets.php'));
    }

    private function registerMiddleware(ConfettiConfig $config): void
    {
        if (! $this->app->bound(Router::class)) {
            return;
        }

        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('laranail-confetti', InjectConfetti::class);

        if (! $config->injectValue('auto', false)) {
            return;
        }

        $group = (string) $config->injectValue('group', 'web');

        if ($router->hasMiddlewareGroup($group)) {
            $router->pushMiddlewareToGroup($group, InjectConfetti::class);

            return;
        }

        // No such group, so fall back to the global stack and the setting still
        // does what it says.
        if ($this->app->bound(Kernel::class)) {
            $kernel = $this->app->make(Kernel::class);

            if (method_exists($kernel, 'pushMiddleware')) {
                $kernel->pushMiddleware(InjectConfetti::class);
            }
        }
    }
}
