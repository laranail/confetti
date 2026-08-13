<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Integrations\Filament;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Simtabi\Laranail\Confetti\View\ConfettiTags;

/*
 * Filament is a development dependency, so this class may have nothing to
 * extend. The guard has to sit here, before the declaration, and cannot be
 * moved inside a method: `implements Plugin` is resolved when PHP compiles the
 * class, so merely autoloading this file without Filament installed is a fatal
 * error. Anything that touches the class map reaches it — a class_exists()
 * probe, `composer dump-autoload --optimize`, static analysis, an IDE helper
 * generator.
 *
 * Returning early leaves the class undefined, which is exactly what
 * class_exists() reports, so callers can check for it in the ordinary way.
 */
if (! interface_exists(Plugin::class)) {
    return;
}

/**
 * Fires confetti inside a Filament panel.
 *
 *     public function panel(Panel $panel): Panel
 *     {
 *         return $panel->plugins([
 *             ConfettiPlugin::make(),
 *         ]);
 *     }
 *
 * Then anywhere in the panel:
 *
 *     Action::make('publish')
 *         ->action(fn () => Confetti::realistic()->shoot());
 *
 * The plugin renders the same Blade component a plain Laravel application uses,
 * so a panel and the rest of the site share one runtime, one bundle and one
 * asset-delivery setting. Nothing is registered with Filament's own asset
 * pipeline by default — that pipeline only covers panel pages, and confetti
 * fired from a marketing page would have nothing to run it.
 */
final class ConfettiPlugin implements Plugin
{
    private string $hook = PanelsRenderHook::BODY_END;

    private Closure|bool $enabled = true;

    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'laranail-confetti';
    }

    /**
     * Render somewhere other than the end of the body.
     *
     * Configurable because render-hook names have shifted between Filament
     * majors, and because a panel with an unusual layout may need the tags
     * elsewhere.
     */
    public function renderHook(string $hook): static
    {
        $this->hook = $hook;

        return $this;
    }

    public function enabled(Closure|bool $enabled = true): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function register(Panel $panel): void
    {
        $panel->renderHook(
            $this->hook,
            fn (): string => $this->isEnabled()
                ? app(ConfettiTags::class)->render()
                : '',
        );
    }

    public function boot(Panel $panel): void
    {
        //
    }

    private function isEnabled(): bool
    {
        return $this->enabled instanceof Closure
            ? (bool) ($this->enabled)()
            : $this->enabled;
    }
}
