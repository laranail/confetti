<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Commands;

use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Confetti\Enums\AssetMode;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\InteractsWithConsoleWriter;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\InteractsWithConsoleServices;
use Simtabi\Laranail\Package\Tools\Commands\InstallCommand as PackageToolsInstallCommand;

/**
 * `laranail::confetti.install` publishes the config and explains what is left
 * to do.
 *
 * The package works without running this: it auto-discovers, and the default
 * asset mode needs no publish step. The command exists to publish the config
 * for editing and to say plainly which of the three ways of getting the runtime
 * onto a page the application has chosen.
 *
 * The base is package-tools' install command, which carries the `::` name
 * support. laranail/console's display API and managed run lifecycle come from
 * its two traits rather than its base class, so neither package has to depend
 * on the other. `handle()` is this command's own: the base's generic publish
 * pipeline would print different steps, and the output here is the contract.
 */
final class InstallCommand extends PackageToolsInstallCommand
{
    use InteractsWithConsoleServices;
    use InteractsWithConsoleWriter;

    public const string SIGNATURE = 'laranail::confetti.install {--force : Overwrite an existing published config}';

    public const string DESCRIPTION = 'Publish the confetti config and print the remaining setup steps';

    public function __construct(Package $package)
    {
        // Listed in `php artisan list`, as it always has been: the base hides
        // install commands by default, so visibility is passed explicitly.
        parent::__construct($package, self::SIGNATURE, hidden: false);

        // The base writes `Install {package}` as the description during
        // construction; restore the one this command has always shown. Both the
        // property and Symfony's copy are set, because the parent constructor
        // has already pushed the property through setDescription().
        $this->description = self::DESCRIPTION;
        $this->setDescription(self::DESCRIPTION);

        // Booted eagerly, as console's own base does, so `$this->services`
        // exists straight after construction.
        $this->bootConsoleSupport();
    }

    /**
     * The config stays method-injected, but optional: the base declares
     * `handle(): int`, and a required parameter would be an incompatible
     * override. Artisan still injects it on every run.
     */
    public function handle(?ConfettiConfig $config = null): int
    {
        $config ??= $this->laravel->make(ConfettiConfig::class);

        $this->callSilently('vendor:publish', array_filter([
            '--tag'   => 'laranail::confetti-config',
            '--force' => $this->option('force') ? true : null,
        ]));

        $this->newLine();
        $this->line('  <fg=green;options=bold>✓</> Published <options=bold>config/laranail/confetti.php</>');

        if ($config->assetMode === AssetMode::Published) {
            $this->callSilently('vendor:publish', ['--tag' => 'laranail::confetti-assets', '--force' => true]);
            $this->line('  <fg=green;options=bold>✓</> Published the browser bundle to <options=bold>public/vendor/confetti</>');
        }

        $this->newLine();
        $this->line('  <options=bold>One step left: get the runtime onto your pages.</>');
        $this->newLine();
        $this->line('  Place the component in your layout, before </body>:');
        $this->line('      <fg=cyan><x-laranail-confetti::scripts /></>');
        $this->newLine();
        $this->line('  Or let the middleware do it for every HTML response:');
        $this->line('      <fg=cyan>CONFETTI_AUTO_INJECT=true</>');
        $this->newLine();
        $this->line('  On a Filament panel, register the plugin instead:');
        $this->line('      <fg=cyan>->plugins([ConfettiPlugin::make()])</>');
        $this->newLine();
        $this->line('  Then fire it:');
        $this->line('      <fg=cyan>Confetti::realistic()->shoot();</>');
        $this->newLine();
        $this->line('  Verify with <options=bold>php artisan laranail::confetti.doctor</>.');
        $this->newLine();

        return self::SUCCESS;
    }
}
