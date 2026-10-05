<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Simtabi\Laranail\Confetti\Enums\AssetMode;
use Symfony\Component\Console\Command\Command;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\Commands\InstallCommand;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\InteractsWithConsoleWriter;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\InteractsWithConsoleServices;
use Simtabi\Laranail\Package\Tools\Commands\InstallCommand as PackageToolsInstallCommand;

/**
 * The install command's observable surface, pinned.
 *
 * Its base class moved from laranail/console's `Command` to laranail/package-tools'
 * `InstallCommand`, with console's display API kept by `use`-ing its two traits. A base swap is
 * where a name, an option, the listing visibility or a line of output changes without anyone
 * deciding it should, so every one of those is asserted here against what the command did before.
 */
function confettiInstallCommand(): Command
{
    $command = Artisan::all()['laranail::confetti.install'] ?? null;

    expect($command)->toBeInstanceOf(InstallCommand::class);

    /** @var Command $command */
    return $command;
}

it('keeps its name, aliases, description and listing visibility', function (): void {
    $command = confettiInstallCommand();

    expect($command->getName())->toBe('laranail::confetti.install')
        ->and($command->getAliases())->toBe([])
        ->and($command->getDescription())->toBe('Publish the confetti config and print the remaining setup steps')
        ->and($command->isHidden())->toBeFalse();
});

it('keeps exactly one option of its own, and no arguments', function (): void {
    $definition = confettiInstallCommand()->getNativeDefinition();

    expect(array_keys($definition->getOptions()))->toBe(['force'])
        ->and($definition->getArguments())->toBe([]);

    $option = $definition->getOption('force');

    expect($option->acceptValue())->toBeFalse()
        ->and($option->getDescription())->toBe('Overwrite an existing published config');
});

it('publishes the config and prints every remaining setup step', function (): void {
    $this->artisan('laranail::confetti.install')
        ->expectsOutputToContain('Published config/laranail/confetti.php')
        ->doesntExpectOutputToContain('public/vendor/confetti')
        ->expectsOutputToContain('One step left: get the runtime onto your pages.')
        ->expectsOutputToContain('<x-laranail-confetti::scripts />')
        ->expectsOutputToContain('CONFETTI_AUTO_INJECT=true')
        ->expectsOutputToContain('->plugins([ConfettiPlugin::make()])')
        ->expectsOutputToContain('Confetti::realistic()->shoot();')
        ->expectsOutputToContain('Verify with php artisan laranail::confetti.doctor.')
        ->assertExitCode(0);
});

it('accepts --force', function (): void {
    $this->artisan('laranail::confetti.install', ['--force' => true])
        ->expectsOutputToContain('Published config/laranail/confetti.php')
        ->assertExitCode(0);
});

it('publishes the browser bundle only in published asset mode', function (): void {
    config()->set('laranail.confetti.assets.mode', AssetMode::Published->value);
    app()->forgetInstance(ConfettiConfig::class);

    $this->artisan('laranail::confetti.install')
        ->expectsOutputToContain('Published the browser bundle to public/vendor/confetti')
        ->assertExitCode(0);
});

it('extends the package-tools install base and keeps both console traits', function (): void {
    expect(is_subclass_of(InstallCommand::class, PackageToolsInstallCommand::class))->toBeTrue();

    $traits = class_uses(InstallCommand::class);

    expect($traits)->toHaveKey(InteractsWithConsoleServices::class)
        ->and($traits)->toHaveKey(InteractsWithConsoleWriter::class);
});
