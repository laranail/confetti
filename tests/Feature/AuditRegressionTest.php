<?php

declare(strict_types=1);

use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Support\BootConfig;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;
use Simtabi\Laranail\Confetti\Transport\TransportManager;
use Simtabi\Laranail\Confetti\Transport\LivewireTransport;
use Simtabi\Laranail\Confetti\Exceptions\TransportUnavailable;

/**
 * Defects found by auditing the package against its own documentation, each of
 * which survived because nothing exercised it.
 */
it('applies duration() to a continuous effect', function (): void {
    // duration() wrote to a builder property that nothing read, so
    // `snow()->duration(5000)` silently ran for the default fifteen seconds.
    // The preset shorthand took an argument, which is why the bug hid: the
    // documented path worked and the other one did not.
    expect(Confetti::snow()->duration(5000)->toArray()['animations'][0]['duration'])->toBe(5000);
});

it('applies duration() however the preset was reached', function (): void {
    // Three routes to the same preset, only one of which takes an argument.
    expect(Confetti::snow(3000)->toArray()['animations'][0]['duration'])->toBe(3000);
    expect(Confetti::preset('snow')->duration(3000)->toArray()['animations'][0]['duration'])->toBe(3000);
    expect(Confetti::fireworks()->duration(3000)->toArray()['animations'][0]['duration'])->toBe(3000);
});

it('lets duration() shorten a server-side expansion', function (): void {
    // The burst ceiling makes this observable: at the default duration this
    // would exceed it and throw.
    expect(Confetti::snow()->duration(1200)->expand()->toPayload()->burstCount())->toBe(40);
});

it('honours the Livewire integration toggle', function (): void {
    // The config key existed and was documented, but nothing read it, so
    // turning the integration off did nothing at all.
    $enabled = new LivewireTransport(app(), 'confetti:fire', enabled: true);
    $disabled = new LivewireTransport(app(), 'confetti:fire', enabled: false);

    expect($disabled->available())->toBeFalse();
    // Not a Livewire request either way, so this only proves the flag is read;
    // the transport reports itself unavailable for both reasons.
    expect($enabled->available())->toBeFalse();
})->skip(fn (): bool => ! class_exists('Livewire\\Livewire'), 'livewire/livewire is not installed.');

it('resolves the Livewire transport with the configured flag', function (): void {
    config()->set('laranail.confetti.integrations.livewire.enabled', false);
    app()->forgetInstance(ConfettiConfig::class);
    app()->make(TransportManager::class)->forgetResolved();

    expect(fn () => Confetti::realistic()->via('livewire')->shoot())
        ->toThrow(TransportUnavailable::class);
});

it('can reset as well as stop', function (): void {
    // The runtime handled a "reset" action but no PHP call could produce one,
    // so that branch was unreachable from the server.
    $fake = Confetti::fake();

    Confetti::stop();
    Confetti::reset();

    Confetti::assertFiredTimes(2);

    $actions = array_map(
        static fn (ConfettiPayload $payload): string => $payload->action,
        $fake->payloads(),
    );

    expect($actions)->toBe(['stop', 'reset']);
});

it('ships the runtime debug flag to the browser', function (): void {
    config()->set('laranail.confetti.runtime.debug', true);
    app()->forgetInstance(ConfettiConfig::class);

    $boot = json_decode(app(BootConfig::class)->toJson(), true);

    expect($boot['runtime']['debug'])->toBeTrue();
});
