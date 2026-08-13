<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Builder\Concerns;

use Simtabi\Laranail\Confetti\Contracts\ExpandableAnimation;
use Simtabi\Laranail\Confetti\Contracts\Shape;
use Simtabi\Laranail\Confetti\Enums\PresetExpansion;
use Simtabi\Laranail\Confetti\Enums\TransportDriver;
use Simtabi\Laranail\Confetti\Payload\Animation;
use Simtabi\Laranail\Confetti\Payload\Burst;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;
use Simtabi\Laranail\Confetti\Payload\Shapes\TextShape;
use Simtabi\Laranail\Confetti\Support\Json;
use Simtabi\Laranail\Confetti\Support\Seed;

/**
 * Committing bursts and sending the result.
 */
trait DispatchesConfetti
{
    /**
     * Commit the options set so far as one burst and start another.
     *
     * Options carry forward by default, so a palette or a scalar set before the
     * first `then()` still applies to the ones after it:
     *
     *     Confetti::colors('#ff0000')->left()->then()->right()->shoot();
     *
     * Both bursts are red. Pass `reset: true` to clear the slate instead, which
     * is the behaviour the original package had.
     */
    public function then(bool $reset = false): static
    {
        $this->commitBurst();

        if ($reset) {
            $this->stack->clearVolatile();
        }

        $this->delay = $this->stagger > 0
            ? $this->delay + $this->stagger
            : 0;

        $this->dirty = false;

        return $this;
    }

    /** Send the effect. */
    public function shoot(): void
    {
        if (! $this->config->enabled) {
            return;
        }

        $payload = $this->toPayload();

        if ($payload->isEmpty()) {
            return;
        }

        $this->transports->send($payload, $this->driver);

        $this->reset();
    }

    /**
     * Force a transport instead of letting one be detected.
     *
     * Kept as a string rather than coerced to the enum, because a custom driver
     * registered with `Confetti::extend()` has no case, and coercing would silently
     * downgrade it to automatic detection and the custom transport would never
     * be called.
     */
    public function via(TransportDriver|string $driver): static
    {
        $this->driver = $driver instanceof TransportDriver ? $driver->value : $driver;

        return $this;
    }

    /**
     * Fix the random sequence used when expanding a continuous effect in PHP.
     *
     * Only has an effect together with {@see expand()}. Without it the
     * randomness happens in the browser, where a server-side seed would be
     * meaningless.
     */
    public function seed(int $seed): static
    {
        $this->seed = $seed;

        return $this;
    }

    /**
     * Walk continuous effects out into concrete bursts here rather than in the
     * browser.
     *
     * The payload grows by orders of magnitude (snow at its default duration
     * is around five hundred bursts) and every visitor sees the same sequence.
     * Worth it when a test needs to assert on the bursts themselves, or when
     * the application does not load this package's runtime; not otherwise.
     */
    public function expand(bool $expand = true): static
    {
        $this->expansion = $expand ? PresetExpansion::Server : PresetExpansion::Client;

        return $this;
    }

    /** Build the payload without sending it. */
    public function toPayload(): ConfettiPayload
    {
        $draft = clone $this->draft;

        // Commit whatever is still on the stack as a final burst: the options
        // set after the last then(), or the whole effect when there was none.
        //
        // Unless a preset produced the bursts: then those options are the
        // preset's own shared settings, already folded into every one of them,
        // and committing again would fire the effect one and a half times.
        if (! $draft->hasPresetOutput() && ($this->dirty || $draft->isEmpty())) {
            $draft->addBurst($this->buildBurst());
        }

        $base = $this->baseOptions();

        if ($this->shouldExpand() && $draft->hasAnimations()) {
            $draft->replaceAnimationsWithBursts($this->expandAnimations($draft->animations($base)));
        }

        $bursts = array_map($this->serialiseBurst(...), $draft->bursts($base));

        $this->validator->burstCount(count($bursts));

        return ConfettiPayload::make(
            bursts: $bursts,
            animations: $draft->animations($base),
            // Only travel when this effect overrides the configured policy;
            // otherwise the runtime already knows it from the boot payload.
            reducedMotion: $this->reducedMotion?->value,
        );
    }

    /** The payload as it goes on the wire: deltas only. */
    public function toArray(): array
    {
        return $this->toPayload()->toArray();
    }

    /**
     * Every burst with the package defaults merged in.
     *
     * The wire format omits anything matching a default, which makes payloads
     * small but assertions awkward. This is the expanded view, for tests and
     * debugging.
     */
    public function toResolvedArray(): array
    {
        $payload = $this->toPayload();
        $defaults = $this->serialiseOptions($this->config->resolvedDefaults());

        return [
            ...$payload->toArray(),
            'bursts' => array_map(
                static fn (Burst $burst): array => [
                    'delay' => $burst->delay,
                    'options' => [...$defaults, ...$burst->options],
                ],
                $payload->bursts,
            ),
        ];
    }

    public function toJson(int $flags = 0): string
    {
        return Json::encodePlain($this->toArray(), $flags);
    }

    /** Discard everything set so far and start over. */
    public function reset(): static
    {
        $this->stack->clearVolatile();
        $this->draft->clear();
        $this->delay = 0;
        $this->stagger = 0;
        $this->duration = null;
        $this->dirty = false;

        return $this;
    }

    private function commitBurst(): void
    {
        $this->draft->addBurst($this->buildBurst());
    }

    private function buildBurst(): Burst
    {
        return new Burst($this->baseOptions(), $this->delay);
    }

    /**
     * The options every burst starts from: everything set so far that differs
     * from the package defaults, in wire form.
     *
     * @return array<string, mixed>
     */
    private function baseOptions(): array
    {
        return $this->serialiseOptions($this->stack->delta());
    }

    /**
     * Serialise a burst, resolving its shapes against its own scalar.
     *
     * A burst carries its own scalar when a preset overrode it: the stars
     * recipe fires 1.2-scale stars alongside 0.75-scale circles, so the text
     * scalar has to be resolved per burst, not once from the builder.
     */
    private function serialiseBurst(Burst $burst): Burst
    {
        $scalar = $burst->options['scalar'] ?? $this->stack->get('scalar') ?? 1.0;

        return new Burst($this->serialiseOptions($burst->options, (float) $scalar), $burst->delay);
    }

    /**
     * Turn resolved options into their wire form.
     *
     * Shapes are the only ones that need work: a text shape with no scalar of
     * its own inherits the burst's, which is what keeps canvas-confetti from
     * rasterising a glyph at one size and then drawing it at another.
     *
     * Idempotent: a shape already reduced to its wire form passes straight
     * through, so bursts can be serialised after being merged.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function serialiseOptions(array $options, ?float $scalar = null): array
    {
        if (! isset($options['shapes']) || ! is_array($options['shapes'])) {
            return $options;
        }

        $scalar ??= (float) ($this->stack->get('scalar') ?? 1.0);

        $options['shapes'] = array_map(
            static function (mixed $shape) use ($scalar): array|string {
                if ($shape instanceof TextShape) {
                    return $shape->withInheritedScalar($scalar)->toWire();
                }

                if ($shape instanceof Shape) {
                    return $shape->toWire();
                }

                // Already serialised.
                return is_array($shape) ? $shape : (string) $shape;
            },
            array_values($options['shapes']),
        );

        return $options;
    }

    private function shouldExpand(): bool
    {
        return ($this->expansion ?? $this->config->expansion) === PresetExpansion::Server;
    }

    /**
     * @param list<Animation> $animations
     * @return list<Burst>
     */
    private function expandAnimations(array $animations): array
    {
        $seed = new Seed($this->seed ?? $this->config->seed ?? 0x9E3779B9);
        $bursts = [];

        foreach ($animations as $animation) {
            $preset = $this->presets->make($animation->animation->value, $animation->duration);

            if (! $preset instanceof ExpandableAnimation) {
                continue;
            }

            $bursts = [...$bursts, ...$preset->expand($animation, $seed)];
        }

        return $bursts;
    }
}
