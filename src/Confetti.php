<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti;

use Closure;
use Illuminate\Contracts\Container\Container;
use Simtabi\Laranail\Confetti\Builder\ConfettiBuilder;
use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Contracts\Transport;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;
use Simtabi\Laranail\Confetti\Payload\PendingBursts;
use Simtabi\Laranail\Confetti\Presets\PresetRegistry;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\Testing\ConfettiFake;
use Simtabi\Laranail\Confetti\Transport\ArrayTransport;
use Simtabi\Laranail\Confetti\Transport\TransportManager;
use Simtabi\Laranail\Confetti\Validation\OptionValidator;

/**
 * The confetti service — bound as a singleton and reached through the
 * {@see Facades\Confetti} facade.
 *
 * Every builder method is available statically through the facade, because
 * unknown calls are forwarded to a fresh builder:
 *
 *     Confetti::realistic()->shoot();      // -> make()->realistic()
 *     Confetti::make()->top()->shoot();    // explicit, identical result
 *
 * Each call starts a new builder, so two effects never share state.
 */
class Confetti
{
    private ?ConfettiFake $fake = null;

    public function __construct(
        private readonly Container $container,
        private readonly ConfettiConfig $config,
        private readonly PresetRegistry $presets,
        private readonly TransportManager $transports,
    ) {}

    /** Start a new effect. */
    public function make(): ConfettiBuilder
    {
        return new ConfettiBuilder(
            config: $this->config,
            validator: new OptionValidator($this->config->limits, $this->config->strict),
            presets: $this->presets,
            transports: $this->transports,
        );
    }

    /**
     * Abort whatever is currently running in the browser.
     *
     * Worth having because the continuous effects last fifteen seconds by
     * default, which is a long time to be stuck with if the user has moved on.
     */
    public function stop(): void
    {
        $this->transports->send(ConfettiPayload::stop());
    }

    /** Clear any effect queued for this request without sending it. */
    public function flush(): void
    {
        $this->container->make(PendingBursts::class)->flush();
    }

    /**
     * Swap the transport for a recorder so tests can assert on what fired.
     *
     *     Confetti::fake();
     *     $this->post('/orders');
     *     Confetti::assertFired();
     */
    public function fake(): ConfettiFake
    {
        $recorder = new ArrayTransport;

        foreach (['auto', 'session', 'livewire', 'inertia', 'null', 'array'] as $driver) {
            $this->transports->swap($driver, $recorder);
        }

        return $this->fake = new ConfettiFake($recorder);
    }

    public function isFaked(): bool
    {
        return $this->fake instanceof ConfettiFake;
    }

    /** The active fake, for the assertion methods proxied through the facade. */
    public function fakeInstance(): ?ConfettiFake
    {
        return $this->fake;
    }

    /** Restore real transports after a fake. */
    public function restore(): void
    {
        $this->fake = null;
        $this->transports->forgetResolved();
    }

    /**
     * Register a custom preset.
     *
     * @param callable(mixed...): Preset $factory
     */
    public function registerPreset(string $name, callable $factory): self
    {
        $this->presets->register($name, $factory);

        return $this;
    }

    /**
     * Register a custom transport.
     *
     * @param Closure(Container): Transport $factory
     */
    public function extend(string $driver, Closure $factory): self
    {
        $this->transports->extend($driver, $factory);

        return $this;
    }

    public function config(): ConfettiConfig
    {
        return $this->config;
    }

    public function presets(): PresetRegistry
    {
        return $this->presets;
    }

    public function transports(): TransportManager
    {
        return $this->transports;
    }

    /** Forward builder calls, so Confetti::snow() reads as well as make()->snow(). */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->make()->{$method}(...$arguments);
    }
}
