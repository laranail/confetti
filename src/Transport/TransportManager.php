<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Transport;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Simtabi\Laranail\Confetti\Contracts\Transport;
use Simtabi\Laranail\Confetti\Enums\TransportDriver;
use Simtabi\Laranail\Confetti\Events\ConfettiFired;
use Simtabi\Laranail\Confetti\Exceptions\TransportUnavailable;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;
use Simtabi\Laranail\Confetti\Payload\PendingBursts;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;

/**
 * Resolves which transport should carry a payload.
 *
 * Follows Laravel's manager convention, so applications can add their own:
 *
 *     Confetti::extend('broadcast', fn ($app) => new BroadcastTransport(...));
 *
 * Automatic resolution walks the drivers in order of specificity and stops at
 * the first available one. It **never throws**; an unresolvable context falls
 * through to {@see NullTransport}, because confetti is decorative and should
 * not be able to break a console command or a queued job. A driver named
 * explicitly through `via()` does throw when unavailable, since asking for
 * something specific and silently getting something else is worse.
 */
final class TransportManager
{
    /** The order automatic resolution tries drivers in. */
    private const array AUTO_ORDER = [
        TransportDriver::Livewire,
        TransportDriver::Inertia,
        TransportDriver::Session,
    ];

    /** @var array<string, Closure(Container): Transport> */
    private array $custom = [];

    /** @var array<string, Transport> */
    private array $resolved = [];

    public function __construct(
        private readonly Container $container,
        private readonly ConfettiConfig $config,
        private readonly ?Dispatcher $events = null,
    ) {}

    /** Register a custom driver. */
    public function extend(string $driver, Closure $factory): self
    {
        $this->custom[$driver] = $factory;
        unset($this->resolved[$driver]);

        return $this;
    }

    /**
     * Resolve a driver by name, or the configured default.
     *
     * @throws TransportUnavailable when a named driver cannot run here
     */
    public function driver(?string $driver = null): Transport
    {
        $name = $driver ?? $this->config->transport->value;

        if ($name === TransportDriver::Auto->value) {
            return $this->detect();
        }

        $transport = $this->resolve($name);

        if (! $transport->available()) {
            throw TransportUnavailable::driver($name, $this->unavailableReason($name));
        }

        return $transport;
    }

    /** The first available driver, or the null transport. */
    public function detect(): Transport
    {
        foreach (self::AUTO_ORDER as $driver) {
            $transport = $this->resolve($driver->value);

            if ($transport->available()) {
                return $transport;
            }
        }

        return $this->resolve(TransportDriver::Null->value);
    }

    /** Hand a payload to a transport and announce it. */
    public function send(ConfettiPayload $payload, ?string $driver = null): Transport
    {
        $transport = $this->driver($driver);

        $transport->send($payload);

        $this->events?->dispatch(new ConfettiFired($payload, $transport->name()));

        return $transport;
    }

    /** Swap in a transport instance, used by Confetti::fake(). */
    public function swap(string $driver, Transport $transport): self
    {
        $this->resolved[$driver] = $transport;

        return $this;
    }

    public function forgetResolved(): self
    {
        $this->resolved = [];

        return $this;
    }

    /** @return list<string> */
    public function available(): array
    {
        $names = array_map(static fn (TransportDriver $d): string => $d->value, TransportDriver::cases());

        return array_values(array_filter(
            [...$names, ...array_keys($this->custom)],
            fn (string $name): bool => $name !== TransportDriver::Auto->value
                && $this->resolve($name)->available(),
        ));
    }

    private function resolve(string $driver): Transport
    {
        return $this->resolved[$driver] ??= $this->build($driver);
    }

    private function build(string $driver): Transport
    {
        if (isset($this->custom[$driver])) {
            return ($this->custom[$driver])($this->container);
        }

        return match ($driver) {
            TransportDriver::Session->value => $this->createSessionDriver(),
            TransportDriver::Livewire->value => $this->createLivewireDriver(),
            TransportDriver::Inertia->value => $this->createInertiaDriver(),
            TransportDriver::Array->value => new ArrayTransport,
            TransportDriver::Null->value => new NullTransport($this->events),
            default => throw TransportUnavailable::unknown(
                $driver,
                [...array_map(static fn (TransportDriver $d): string => $d->value, TransportDriver::cases()),
                    ...array_keys($this->custom)],
            ),
        };
    }

    private function createSessionDriver(): Transport
    {
        // No session bound at all: console, queue, or a route outside the web
        // middleware group. Reaching for session() here is what made the
        // previous implementation throw inside queue workers.
        if (! $this->container->bound('session.store')) {
            return new NullTransport($this->events, 'No session is bound in this context.');
        }

        /** @var Session $session */
        $session = $this->container->make('session.store');

        return new SessionTransport(
            session: $session,
            pending: $this->container->make(PendingBursts::class),
            key: $this->config->sessionKey,
        );
    }

    private function createLivewireDriver(): Transport
    {
        return new LivewireTransport($this->container, $this->config->event);
    }

    private function createInertiaDriver(): Transport
    {
        /** @var Request $request */
        $request = $this->container->make('request');

        return new InertiaTransport(
            request: $request,
            prop: $this->config->inertiaProp,
            enabled: $this->config->integrationEnabled('inertia', false),
        );
    }

    private function unavailableReason(string $driver): string
    {
        return match ($driver) {
            TransportDriver::Session->value => 'no session has been started for this request',
            TransportDriver::Livewire->value => 'this is not a Livewire request, or no component is handling it',
            TransportDriver::Inertia->value => 'this is not an Inertia request, or the integration is disabled',
            default => 'the driver reported itself unavailable',
        };
    }
}
