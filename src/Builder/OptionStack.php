<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Builder;

/**
 * Confetti options held in three ordered layers.
 *
 * Options arrive from three places — package defaults, a preset, and the
 * caller — and the only sane precedence is that order. Keeping them in separate
 * layers and merging at the end, rather than merging as they arrive, buys three
 * things:
 *
 * **Order independence.** `->spread(90)->fireworks()` and
 * `->fireworks()->spread(90)` produce the same burst, because a preset can only
 * ever write to the `preset` layer and the caller only ever to `user`.
 *
 * **No accidental clobbering.** The bug this replaces was a single
 * `array_merge()` with its arguments the wrong way round, which let the generic
 * defaults overwrite the very values the preset existed to set — fireworks came
 * out as a narrow 70-degree puff instead of a 360-degree burst. With layers
 * there is no argument order to get wrong.
 *
 * **A cheap wire format.** {@see delta()} returns only what differs from the
 * defaults, and the defaults travel to the browser once rather than in every
 * burst.
 *
 * Merging is shallow and deliberately so: `origin`, `colors` and `shapes` are
 * whole values, and a caller setting `origin` means to replace it, not to merge
 * one axis into the preset's other axis.
 */
final class OptionStack
{
    public const string LAYER_DEFAULTS = 'defaults';

    public const string LAYER_PRESET = 'preset';

    public const string LAYER_USER = 'user';

    /** @var array<string, mixed> */
    private array $preset = [];

    /** @var array<string, mixed> */
    private array $user = [];

    /** @param array<string, mixed> $defaults */
    public function __construct(private array $defaults = []) {}

    /** @param array<string, mixed> $defaults */
    public function withDefaults(array $defaults): self
    {
        $this->defaults = $defaults;

        return $this;
    }

    public function setUser(string $key, mixed $value): self
    {
        $this->user[$key] = $value;

        return $this;
    }

    /** @param array<string, mixed> $values */
    public function setUserMany(array $values): self
    {
        foreach ($values as $key => $value) {
            $this->user[$key] = $value;
        }

        return $this;
    }

    public function setPreset(string $key, mixed $value): self
    {
        $this->preset[$key] = $value;

        return $this;
    }

    /** @param array<string, mixed> $values */
    public function setPresetMany(array $values): self
    {
        foreach ($values as $key => $value) {
            $this->preset[$key] = $value;
        }

        return $this;
    }

    /**
     * The value a burst would use for this key, respecting layer precedence.
     */
    public function get(string $key, mixed $fallback = null): mixed
    {
        return $this->user[$key]
            ?? $this->preset[$key]
            ?? $this->defaults[$key]
            ?? $fallback;
    }

    /** Whether the caller set this key explicitly. */
    public function userHas(string $key): bool
    {
        return array_key_exists($key, $this->user);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->user)
            || array_key_exists($key, $this->preset)
            || array_key_exists($key, $this->defaults);
    }

    public function forget(string $key): self
    {
        unset($this->user[$key], $this->preset[$key]);

        return $this;
    }

    /** Every option a burst would carry, defaults included. */
    public function resolve(): array
    {
        return array_replace($this->defaults, $this->preset, $this->user);
    }

    /**
     * Only the options that differ from the defaults.
     *
     * This is what goes on the wire. The browser applies the same precedence —
     * canvas-confetti's own defaults, then the package defaults from the boot
     * payload, then these — so the result is identical to {@see resolve()}
     * without repeating the shared values in every burst.
     */
    public function delta(): array
    {
        $resolved = array_replace($this->preset, $this->user);
        $delta = [];

        foreach ($resolved as $key => $value) {
            if (array_key_exists($key, $this->defaults) && $this->defaults[$key] === $value) {
                continue;
            }

            $delta[$key] = $value;
        }

        return $delta;
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return $this->defaults;
    }

    public function clearPreset(): self
    {
        $this->preset = [];

        return $this;
    }

    public function clearUser(): self
    {
        $this->user = [];

        return $this;
    }

    /** Drop everything the defaults did not supply. */
    public function clearVolatile(): self
    {
        $this->preset = [];
        $this->user = [];

        return $this;
    }

    public function isPristine(): bool
    {
        return $this->preset === [] && $this->user === [];
    }
}
