<?php

declare(strict_types=1);

use Simtabi\Laranail\Confetti\Confetti;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\Transport\TransportManager;
use Simtabi\Laranail\Confetti\Exceptions\TransportUnavailable;
use Simtabi\Laranail\Confetti\Facades\Confetti as ConfettiFacade;
use Simtabi\Laranail\Confetti\Integrations\Filament\Providers\FilamentServiceProvider;

/**
 * Every integration toggle must change something.
 *
 * `integrations.livewire.enabled` shipped without a reader, so turning it off
 * did nothing at all. The declaration check in tests/Arch/DeclarationTest.php
 * cannot catch that on its own: the key's leaf is `enabled`, a word that
 * appears throughout the codebase, so a string search finds it whether or not
 * this particular key is ever looked up.
 *
 * The only reliable test is behavioural. Flip each toggle and require the
 * package to behave differently.
 */
function withConfig(string $key, mixed $value): void
{
    config()->set("laranail.confetti.{$key}", $value);

    // Config is parsed once into a ConfettiConfig and both the service and the
    // transport manager are singletons holding that object, so forgetting the
    // config alone leaves them reading the copy they captured at boot. Nothing
    // changes config mid-request in production; a test that does has to rebuild
    // the chain.
    app()->forgetInstance(ConfettiConfig::class);
    app()->forgetInstance(TransportManager::class);
    app()->forgetInstance(Confetti::class);
}

/**
 * Read the flag the manager actually handed the transport.
 *
 * Asserting that a disabled transport is unavailable would pass for the wrong
 * reason: outside a Livewire or Inertia request it is unavailable anyway, so
 * the assertion holds whether or not the flag was ever consulted. Dropping the
 * argument from the constructor call falls back to its default of true, which
 * such a test cannot see. This looks at the value that was passed.
 */
function transportFlag(string $factory): bool
{
    $transport = new ReflectionMethod(TransportManager::class, $factory)
        ->invoke(app(TransportManager::class));

    return (bool) new ReflectionProperty($transport, 'enabled')->getValue($transport);
}

it('passes the Livewire integration toggle through to the transport', function (): void {
    withConfig('integrations.livewire.enabled', true);
    expect(transportFlag('createLivewireDriver'))->toBeTrue();

    withConfig('integrations.livewire.enabled', false);
    expect(transportFlag('createLivewireDriver'))->toBeFalse();
});

it('passes the Inertia integration toggle through to the transport', function (): void {
    withConfig('integrations.inertia.enabled', true);
    expect(transportFlag('createInertiaDriver'))->toBeTrue();

    withConfig('integrations.inertia.enabled', false);
    expect(transportFlag('createInertiaDriver'))->toBeFalse();
});

it('reports a disabled transport as unavailable', function (): void {
    withConfig('integrations.livewire.enabled', false);

    expect(fn () => ConfettiFacade::realistic()->via('livewire')->shoot())
        ->toThrow(TransportUnavailable::class);
});

it('stops rendering into Filament panels when the integration is off', function (): void {
    // The automatic mode is the only thing this flag gates. Registering the
    // plugin by hand is an explicit act and stays honoured either way.
    withConfig('integrations.filament.enabled', false);
    withConfig('integrations.filament.auto', true);

    $provider = new FilamentServiceProvider(app());

    $shouldRegister = new ReflectionMethod($provider, 'shouldRegister');

    expect($shouldRegister->invoke($provider))->toBeFalse();
})->skip(
    fn (): bool => ! interface_exists('Filament\\Contracts\\Plugin'),
    'filament/filament is not installed.',
);

it('keeps the Filament automatic mode off unless both flags are set', function (): void {
    withConfig('integrations.filament.enabled', true);
    withConfig('integrations.filament.auto', false);

    $provider = new FilamentServiceProvider(app());

    expect(new ReflectionMethod($provider, 'shouldRegister')->invoke($provider))->toBeFalse();

    withConfig('integrations.filament.auto', true);

    expect(new ReflectionMethod($provider, 'shouldRegister')->invoke($provider))->toBeTrue();
})->skip(
    fn (): bool => ! interface_exists('Filament\\Contracts\\Plugin'),
    'filament/filament is not installed.',
);

it('stops sending anything when the package is disabled', function (): void {
    withConfig('enabled', false);

    ConfettiFacade::fake();
    ConfettiFacade::realistic()->shoot();

    ConfettiFacade::assertNothingFired();
});
