<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Commands;

use Simtabi\Laranail\Confetti\Enums\AssetMode;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

/**
 * `laranail::confetti.install` publishes the config and explains what is left
 * to do.
 *
 * The package works without running this: it auto-discovers, and the default
 * asset mode needs no publish step. The command exists to publish the config
 * for editing and to say plainly which of the three ways of getting the runtime
 * onto a page the application has chosen.
 */
final class InstallCommand extends Command
{
    use SupportsNamespacedNames;

    protected $signature = 'laranail::confetti.install {--force : Overwrite an existing published config}';

    protected $description = 'Publish the confetti config and print the remaining setup steps';

    public function handle(ConfettiConfig $config): int
    {
        $this->callSilently('vendor:publish', array_filter([
            '--tag' => 'laranail::confetti-config',
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
