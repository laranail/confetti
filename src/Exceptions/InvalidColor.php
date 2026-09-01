<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Exceptions;

use InvalidArgumentException;

/**
 * A colour canvas-confetti cannot read.
 *
 * The library parses colours by stripping every non-hex character from the
 * string and reading what is left, so a CSS colour name or an `rgb()` call does
 * not fail; it silently becomes a different colour. `'red'` survives as `'ed'`,
 * gets expanded as shorthand, and paints something arbitrary. Rejecting
 * non-hex input up front is the only way to make that visible.
 */
final class InvalidColor extends InvalidArgumentException implements ConfettiException
{
    public static function notHex(mixed $value): self
    {
        $shown = is_string($value) ? "'{$value}'" : get_debug_type($value);

        return new self(
            "Confetti colours must be hex strings such as '#26ccff' or '#fff', got {$shown}. "
            .'canvas-confetti strips non-hex characters rather than failing, so a CSS colour name '
            .'or an rgb() value would render as an arbitrary colour instead of raising an error.',
        );
    }

    public static function emptyPalette(): self
    {
        return new self(
            'A confetti palette needs at least one colour. '
            .'Pass hex strings to colors(), or omit the call to use the configured default palette.',
        );
    }

    public static function unknownPalette(string $name, array $available): self
    {
        $list = $available === [] ? 'none are configured' : "'".implode("', '", $available)."'";

        return new self("Unknown confetti palette '{$name}'. Available palettes: {$list}.");
    }
}
