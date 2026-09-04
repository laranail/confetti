<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Contracts\Transport;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;
use Simtabi\Laranail\Confetti\Transport\NullTransport;
use Simtabi\Laranail\Confetti\Events\ConfettiDiscarded;
use Simtabi\Laranail\Confetti\Transport\SessionTransport;
use Simtabi\Laranail\Confetti\Transport\TransportManager;
use Simtabi\Laranail\Confetti\Transport\LivewireTransport;
use Simtabi\Laranail\Confetti\Exceptions\TransportUnavailable;

it('discards the payload outside HTTP instead of throwing', function (): void {
    // The old implementation reached for session() unconditionally, so firing
    // confetti from a console command or a queued job was fatal. It is a
    // mistake, but not one worth crashing a worker over.
    $this->app['session.store']->flush();

    $transport = app(TransportManager::class)->detect();

    expect($transport)->toBeInstanceOf(NullTransport::class);
});

it('does not throw when confetti is fired from a console command', function (): void {
    Artisan::command('test:celebrate', function (): void {
        Confetti::realistic()->shoot();
    });

    expect(fn () => Artisan::call('test:celebrate'))->not->toThrow(Throwable::class);
});

it('announces a discarded payload for anyone who wants to know', function (): void {
    Event::fake([ConfettiDiscarded::class]);

    app(TransportManager::class)->driver('null')->send(
        Confetti::count(10)->toPayload(),
    );

    Event::assertDispatched(ConfettiDiscarded::class);
});

it('resolves the session transport once a session has started', function (): void {
    $this->startSession();

    expect(app(TransportManager::class)->detect())->toBeInstanceOf(SessionTransport::class);
});

it('throws when a driver is named explicitly but cannot run', function (): void {
    // Automatic resolution degrades quietly; asking for something specific and
    // silently getting something else would be worse.
    expect(fn () => app(TransportManager::class)->driver('livewire'))
        ->toThrow(TransportUnavailable::class, 'not a Livewire request');
});

it('probes Livewire without calling back into itself', function (): void {
    // The duck-typed probe calls [self::MANAGER, 'isLivewireRequest'], a
    // dynamic call on a class that may not exist. Rewriting that to a
    // first-class callable rebinds it to this class, which has a private method
    // of the same name, and the probe then recurses until the stack gives out.
    // It is invisible to the type checker and it kills the process rather than
    // failing an assertion, so it is worth pinning down explicitly.
    $livewire = new LivewireTransport(app(), 'confetti:fire');

    expect($livewire->available())->toBeFalse();
    expect($livewire->name())->toBe('livewire');
    // Named as a string, like the transport itself does, so this file has no
    // compiled Livewire reference either.
})->skip(fn (): bool => ! class_exists('Livewire\\Livewire'), 'livewire/livewire is not installed.');

it('accepts a custom transport', function (): void {
    $received = new ArrayObject;

    Confetti::extend('memory', fn (): Transport => new readonly class($received) implements Transport
    {
        public function __construct(private ArrayObject $received) {}

        public function name(): string
        {
            return 'memory';
        }

        public function available(): bool
        {
            return true;
        }

        public function send(ConfettiPayload $payload): void
        {
            $this->received[] = $payload;
        }
    });

    Confetti::count(7)->via('memory')->shoot();

    expect($received)->toHaveCount(1);
    expect($received[0]->bursts[0]->options['particleCount'])->toBe(7);
});
