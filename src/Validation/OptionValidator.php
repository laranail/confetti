<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Validation;

use Illuminate\Support\Facades\Log;
use Simtabi\Laranail\Confetti\Exceptions\InvalidOption;

/**
 * Range-checks the numeric confetti options as they are set.
 *
 * Validation runs in the setter rather than at serialisation so the stack trace
 * points at the line that supplied the bad value, not at a `toArray()` call
 * several frames away.
 *
 * Two rules are worth spelling out, because both look like oversights:
 *
 * - **`origin` is not clamped to 0..1.** Off-screen origins are load-bearing in
 *   the upstream recipes — fireworks and snow both launch from `y ≈ -0.2` so
 *   particles fall in from above the viewport. Clamping would quietly break
 *   them.
 * - **`gravity` and `drift` accept negatives.** Negative gravity floats
 *   particles upward, which is how the emoji and stars recipes hang confetti in
 *   place.
 *
 * In non-strict mode a bad value is clamped and logged once instead of
 * throwing, on the grounds that a decorative effect should not be able to break
 * a checkout page. Strict is the default, because an effect that silently
 * differs between staging and production is worse than an exception a test
 * would have caught.
 */
final readonly class OptionValidator
{
    public function __construct(
        private Limits $limits = new Limits,
        private bool $strict = true,
    ) {}

    public static function default(): self
    {
        return new self;
    }

    public function limits(): Limits
    {
        return $this->limits;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }

    public function particleCount(int $value): int
    {
        return (int) $this->between('particleCount', $value, 0, $this->limits->maxParticles);
    }

    public function spread(float|int $value): float
    {
        return $this->between('spread', $value, 0, 360);
    }

    /** Normalised into `[0, 360)`; canvas-confetti treats 90 as straight up. */
    public function angle(float|int $value): float
    {
        $angle = $this->finite('angle', $value);
        $angle = fmod($angle, 360.0);

        return $angle < 0 ? $angle + 360.0 : $angle;
    }

    public function startVelocity(float|int $value): float
    {
        return $this->between('startVelocity', $value, 0, null);
    }

    /**
     * A per-frame velocity multiplier, so it must sit strictly inside 0..1.
     * At 1 or above particles never slow down and the burst runs until it
     * exhausts its tick budget.
     */
    public function decay(float|int $value): float
    {
        $decay = $this->finite('decay', $value);

        if ($decay > 0.0 && $decay < 1.0) {
            return $decay;
        }

        if ($this->strict) {
            throw InvalidOption::decayOutOfRange($decay);
        }

        return $this->clampAndLog('decay', $decay, 0.01, 0.99);
    }

    public function gravity(float|int $value): float
    {
        return $this->finite('gravity', $value);
    }

    public function drift(float|int $value): float
    {
        return $this->finite('drift', $value);
    }

    public function ticks(int $value): int
    {
        return (int) $this->between('ticks', $value, 1, $this->limits->maxTicks);
    }

    public function scalar(float|int $value): float
    {
        $scalar = $this->finite('scalar', $value);

        if ($scalar > 0.0 && $scalar <= 10.0) {
            return $scalar;
        }

        if ($this->strict) {
            throw InvalidOption::outOfRange('scalar', $scalar, 0, 10);
        }

        return $this->clampAndLog('scalar', $scalar, 0.1, 10.0);
    }

    public function zIndex(int $value): int
    {
        return $value;
    }

    public function delay(int $value): int
    {
        return (int) $this->between('delay', $value, 0, $this->limits->maxDelay);
    }

    public function duration(int $value): int
    {
        return (int) $this->between('duration', $value, 1, $this->limits->maxDuration);
    }

    /**
     * Origins are finite but unbounded — see the class docblock for why they are
     * deliberately not clamped to the viewport.
     */
    public function origin(float|int $x, float|int $y): array
    {
        return [
            'x' => $this->finite('origin.x', $x),
            'y' => $this->finite('origin.y', $y),
        ];
    }

    public function burstCount(int $count): int
    {
        if ($count <= $this->limits->maxBursts) {
            return $count;
        }

        throw InvalidOption::overLimit('bursts', $count, $this->limits->maxBursts);
    }

    private function finite(string $option, float|int $value): float
    {
        $float = (float) $value;

        if (! is_finite($float)) {
            if ($this->strict) {
                throw InvalidOption::notFinite($option, $value);
            }

            $this->log($option, $value, 0.0);

            return 0.0;
        }

        return $float;
    }

    private function between(string $option, float|int $value, float|int|null $min, float|int|null $max): float
    {
        $float = $this->finite($option, $value);

        $tooLow = $min !== null && $float < $min;
        $tooHigh = $max !== null && $float > $max;

        if (! $tooLow && ! $tooHigh) {
            return $float;
        }

        if ($this->strict) {
            throw InvalidOption::outOfRange($option, $float, $min, $max);
        }

        return $this->clampAndLog($option, $float, $min, $max);
    }

    private function clampAndLog(string $option, float $value, float|int|null $min, float|int|null $max): float
    {
        $clamped = $value;

        if ($min !== null && $clamped < $min) {
            $clamped = (float) $min;
        }

        if ($max !== null && $clamped > $max) {
            $clamped = (float) $max;
        }

        $this->log($option, $value, $clamped);

        return $clamped;
    }

    private function log(string $option, mixed $from, float $to): void
    {
        Log::warning('laranail/confetti: clamped an out-of-range option.', [
            'option' => $option,
            'given' => $from,
            'used' => $to,
            'hint' => 'Set laranail.confetti.validation.strict to true to raise instead of clamping.',
        ]);
    }
}
