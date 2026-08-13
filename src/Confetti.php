<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\Confetti\Builder\ConfettiBuilder;
use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Contracts\Transport;
use Simtabi\Laranail\Confetti\Events\ConfettiPreparing;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;
use Simtabi\Laranail\Confetti\Payload\PendingBursts;
use Simtabi\Laranail\Confetti\Presets\PresetRegistry;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\Support\EffectRegistry;
use Simtabi\Laranail\Confetti\Testing\ConfettiFake;
use Simtabi\Laranail\Confetti\Transport\ArrayTransport;
use Simtabi\Laranail\Confetti\Transport\TransportManager;
use Simtabi\Laranail\Confetti\Validation\OptionValidator;

/**
 * The confetti service, bound as a singleton and reached through the
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

    /** @var list<callable(ConfettiBuilder): void> */
    private array $before = [];

    public function __construct(
        private readonly Container $container,
        private readonly ConfettiConfig $config,
        private readonly PresetRegistry $presets,
        private readonly TransportManager $transports,
        private readonly EffectRegistry $effects = new EffectRegistry,
        private readonly ?Dispatcher $events = null,
    ) {}

    /** Start a new effect. */
    public function make(): ConfettiBuilder
    {
        $builder = new ConfettiBuilder(
            config: $this->config,
            validator: new OptionValidator($this->config->limits, $this->config->strict),
            presets: $this->presets,
            transports: $this->transports,
        );

        // Hooks first, then the event. Both receive the same mutable builder;
        // the hooks are the cheap in-process path and the event is for
        // listeners that live somewhere other than a service provider.
        foreach ($this->before as $hook) {
            $hook($builder);
        }

        $this->events?->dispatch(new ConfettiPreparing($builder));

        return $builder;
    }

    /**
     * Apply something to every effect the application creates.
     *
     *     Confetti::before(fn (ConfettiBuilder $b) => $b->palette('brand'));
     *
     * Runs when the builder is made, so anything the caller sets afterwards
     * still wins. That is the useful order: a hook sets the house style and
     * individual calls override it.
     *
     * @param callable(ConfettiBuilder): void $hook
     */
    public function before(callable $hook): self
    {
        $this->before[] = $hook;

        return $this;
    }

    /** Drop every registered hook. Mostly for tests. */
    public function forgetHooks(): self
    {
        $this->before = [];

        return $this;
    }

    /**
     * Start an effect from a named configuration.
     *
     *     Confetti::effect('checkout')->shoot();
     *
     * The name comes from `laranail.confetti.effects`, so what "checkout" looks
     * like is a config decision rather than a code one.
     */
    public function effect(string $name): ConfettiBuilder
    {
        return $this->effects->apply($name, $this->make());
    }

    public function effects(): EffectRegistry
    {
        return $this->effects;
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

    /**
     * Stop everything and clear the canvas.
     *
     * Stronger than {@see stop()}: that lets particles already in the air
     * finish falling, this removes them.
     */
    public function reset(): void
    {
        $this->transports->send(ConfettiPayload::reset());
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
     * Register a named effect at runtime.
     *
     * The config file is the usual home for these. This is for a package or a
     * provider shipping its own.
     *
     * @param array<string, mixed> $definition
     */
    public function registerEffect(string $name, array $definition): self
    {
        $this->effects->register($name, $definition);

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
