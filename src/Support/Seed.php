<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Support;

/**
 * A small deterministic pseudo-random generator for server-side expansion.
 *
 * Expanding a continuous effect in PHP has one purpose (producing a payload a
 * test can assert against) and that only works if the sequence is reproducible.
 * Reseeding PHP's global generator would achieve it while silently changing the
 * randomness of everything else in the request, so this carries its own state
 * instead.
 *
 * The algorithm is xorshift32: three shifts and three xors, a period of 2^32-1,
 * and no dependency on the platform's RNG. It is not cryptographic and is not
 * meant to be; it decides where a paper triangle lands.
 */
final class Seed
{
    private int $state;

    public function __construct(int $seed = 0x9E3779B9)
    {
        // xorshift cannot escape a zero state, so substitute the golden-ratio
        // constant that seeds it well.
        $normalised = $seed & 0xFFFFFFFF;

        $this->state = $normalised === 0 ? 0x9E3779B9 : $normalised;
    }

    /** A raw 32-bit step. */
    public function next(): int
    {
        $x = $this->state;

        $x ^= ($x << 13) & 0xFFFFFFFF;
        $x ^= $x >> 17;
        $x ^= ($x << 5) & 0xFFFFFFFF;

        return $this->state = $x & 0xFFFFFFFF;
    }

    /** A float in `[0, 1)`, matching `Math.random()`. */
    public function float(): float
    {
        return $this->next() / 4294967296.0;
    }

    /** A float in `[$min, $max)`. */
    public function between(float $min, float $max): float
    {
        return $min + $this->float() * ($max - $min);
    }

    /** Round to a fixed precision so expanded payloads stay compact. */
    public function betweenRounded(float $min, float $max, int $precision = 4): float
    {
        return round($this->between($min, $max), $precision);
    }
}
