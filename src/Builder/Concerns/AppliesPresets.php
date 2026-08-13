<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Builder\Concerns;

use Simtabi\Laranail\Confetti\Enums\ConfettiPreset;

/**
 * The ready-made effects.
 *
 * Presets write to the stack's `preset` layer, never the `user` layer, so
 * anything the caller sets wins regardless of which came first. Four of them
 * (`stars`, `realistic`, `emoji` and the three continuous effects) are faithful
 * ports of the canvas-confetti demo recipes, down to the particle counts and
 * timings; the rest are this package's own.
 *
 * `fireworks`, `snow` and `schoolPride` are continuous. They emit a compact
 * descriptor that the browser expands into an animation loop rather than
 * hundreds of pre-computed bursts. Call `expand()` to do that work in PHP
 * instead.
 */
trait AppliesPresets
{
    /** Apply a preset by name or case. */
    public function preset(ConfettiPreset|string $preset, mixed ...$args): static
    {
        $name = $preset instanceof ConfettiPreset ? $preset->value : $preset;

        $this->presets->make($name, ...$args)->apply($this->stack, $this->draft);

        return $this;
    }

    /**
     * Gold stars and circles, fired three times in quick succession.
     *
     * Note this is the full upstream recipe: six bursts with their own
     * gravity, decay and tick settings, not merely a gold palette. Earlier
     * versions of this API only changed the colours; for that, use
     * `palette('gold')->shapes('star')->scalar(1.2)`.
     */
    public function stars(): static
    {
        return $this->preset(ConfettiPreset::Stars);
    }

    /** A green palette. Options only, so combine it with a position or preset. */
    public function success(): static
    {
        return $this->preset(ConfettiPreset::Success);
    }

    /** Small purple and cyan circles. Options only. */
    public function magic(): static
    {
        return $this->preset(ConfettiPreset::Magic);
    }

    /** A wide, slow fall from the top of the viewport. Options only. */
    public function rain(): static
    {
        return $this->preset(ConfettiPreset::Rain);
    }

    /**
     * Five overlapping bursts at different spreads and velocities, which reads
     * as one substantial explosion rather than five events.
     */
    public function realistic(): static
    {
        return $this->preset(ConfettiPreset::Realistic);
    }

    /** Emoji particles, hanging in place and fading rather than falling. */
    public function emoji(string $text = '🦄'): static
    {
        return $this->preset(ConfettiPreset::Emoji, $text);
    }

    /** Bursts climbing the screen at intervals, thinning out as time runs down. */
    public function fireworks(?int $duration = null): static
    {
        return $this->preset(ConfettiPreset::Fireworks, $duration ?? $this->config->presetDuration);
    }

    /** Single particles drifting down, one per frame. */
    public function snow(?int $duration = null): static
    {
        return $this->preset(ConfettiPreset::Snow, $duration ?? $this->config->presetDuration);
    }

    /** Two jets firing inward from the left and right edges. */
    public function schoolPride(?int $duration = null): static
    {
        return $this->preset(ConfettiPreset::SchoolPride, $duration ?? $this->config->presetDuration);
    }
}
