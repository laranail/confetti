<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Payload;

/**
 * The mutable buffer a builder fills before freezing it into a payload.
 *
 * Bursts come from two places and are resolved differently, which is the only
 * complication here.
 *
 * A burst committed by `then()` is **complete**: it captured the options in
 * force at that moment, and later calls must not reach back and change it;
 * that is the whole point of committing.
 *
 * A burst produced by a preset is **partial**: it holds only what makes that
 * burst different from its siblings. `realistic()` stores five entries of two
 * or three options each, and the shared settings are merged in at
 * serialisation. Deferring that merge is what lets options set *after* the
 * preset still apply to it, so `realistic()->colors(...)` and
 * `colors(...)->realistic()` agree.
 *
 * Presets write here rather than reaching into the builder, which keeps them
 * ignorant of how options are layered and testable on their own.
 */
final class PayloadDraft
{
    /** @var list<array{burst: Burst, inherits: bool}> */
    private array $entries = [];

    /** @var list<Animation> */
    private array $animations = [];

    /** Add a burst that already carries every option it needs. */
    public function addBurst(Burst $burst): void
    {
        $this->entries[] = ['burst' => $burst, 'inherits' => false];
    }

    /**
     * Add a burst holding only its own overrides.
     *
     * @param array<string, mixed> $overrides
     */
    public function addPresetBurst(array $overrides, int $delay = 0): void
    {
        $this->entries[] = ['burst' => new Burst($overrides, $delay), 'inherits' => true];
    }

    public function addAnimation(Animation $animation): void
    {
        $this->animations[] = $animation;
    }

    /**
     * Resolve every burst, merging the shared options into the partial ones.
     *
     * @param array<string, mixed> $base
     * @return list<Burst>
     */
    public function bursts(array $base = []): array
    {
        return array_map(
            static fn (array $entry): Burst => $entry['inherits']
                ? new Burst([...$base, ...$entry['burst']->options], $entry['burst']->delay)
                : $entry['burst'],
            $this->entries,
        );
    }

    /**
     * Resolve every animation, merging the shared options underneath its own.
     *
     * Animation presets write their settings to the stack's preset layer rather
     * than into the descriptor, so a caller's `colors()` reaches the loop
     * whichever side of the preset call it lands on.
     *
     * @param array<string, mixed> $base
     * @return list<Animation>
     */
    public function animations(array $base = []): array
    {
        if ($base === []) {
            return $this->animations;
        }

        return array_map(
            static fn (Animation $animation): Animation => $animation->withOptions(
                [...$base, ...$animation->options],
            ),
            $this->animations,
        );
    }

    public function isEmpty(): bool
    {
        return $this->entries === [] && $this->animations === [];
    }

    public function hasBursts(): bool
    {
        return $this->entries !== [];
    }

    /**
     * Whether a preset has already said what this effect is.
     *
     * When it has, the builder must not also commit the options still sitting
     * on the stack. Those are the preset's own shared settings, and turning
     * them into an extra burst would fire the effect one and a half times.
     * Bursts committed by `then()` do not count: the caller is composing by
     * hand, and anything left over afterwards is a burst they intend.
     */
    public function hasPresetOutput(): bool
    {
        if ($this->animations !== []) {
            return true;
        }

        return array_any($this->entries, fn (array $entry): bool => $entry['inherits']);
    }

    public function hasAnimations(): bool
    {
        return $this->animations !== [];
    }

    public function clear(): void
    {
        $this->entries = [];
        $this->animations = [];
    }

    /**
     * Swap the animation descriptors for the bursts they expand to.
     *
     * @param list<Burst> $bursts
     */
    public function replaceAnimationsWithBursts(array $bursts): void
    {
        $this->animations = [];

        foreach ($bursts as $burst) {
            $this->entries[] = ['burst' => $burst, 'inherits' => true];
        }
    }
}
