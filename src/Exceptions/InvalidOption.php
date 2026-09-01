<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Exceptions;

use InvalidArgumentException;

/**
 * A confetti option outside the range canvas-confetti can use meaningfully.
 */
final class InvalidOption extends InvalidArgumentException implements ConfettiException
{
    public static function outOfRange(string $option, float|int $value, float|int|null $min, float|int|null $max): self
    {
        $range = match (true) {
            $min !== null && $max !== null => "between {$min} and {$max}",
            $min !== null => "at least {$min}",
            $max !== null => "at most {$max}",
            default => 'within its documented range',
        };

        return new self("Confetti option '{$option}' must be {$range}, got {$value}.");
    }

    public static function notFinite(string $option, mixed $value): self
    {
        return new self(
            "Confetti option '{$option}' must be a finite number, got ".get_debug_type($value).'.',
        );
    }

    public static function decayOutOfRange(float $value): self
    {
        return new self(
            "Confetti option 'decay' must be greater than 0 and less than 1, got {$value}. "
            .'It is a per-frame velocity multiplier, so a value of 1 or more means particles never slow down '
            .'and the effect runs until it exhausts its tick budget.',
        );
    }

    public static function overLimit(string $what, int $count, int $limit): self
    {
        return new self(
            "Expanding this effect produced {$count} {$what}, over the configured limit of {$limit}. "
            .'Shorten the duration, raise laranail.confetti.limits.max_bursts, or drop expand() and let the '
            .'browser run the animation loop instead, which is both the default and far smaller on the wire.',
        );
    }
}
