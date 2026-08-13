<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Exceptions;

use InvalidArgumentException;
use Simtabi\Laranail\Confetti\Enums\ConfettiShape;

/**
 * A particle shape canvas-confetti cannot draw.
 *
 * The library's draw routine handles `circle` and `star` explicitly and falls
 * through to a square for everything else, so an unrecognised name renders
 * without complaint. Validating against the closed set turns a silent wrong
 * result into an error that names the alternatives.
 */
final class InvalidShape extends InvalidArgumentException implements ConfettiException
{
    public static function unknown(mixed $value): self
    {
        $shown = is_string($value) ? "'{$value}'" : get_debug_type($value);
        $allowed = implode("', '", ConfettiShape::values());

        return new self(
            "Unknown confetti shape {$shown}. canvas-confetti draws only '{$allowed}' natively "
            .'; any other name renders as a square without warning. '
            .'For anything else use shapeFromPath() or shapeFromText().'
        );
    }

    public static function emptyPath(): self
    {
        return new self('shapeFromPath() needs a non-empty SVG path, such as "M0 10 L5 0 L10 10z".');
    }

    public static function badMatrix(int $count): self
    {
        return new self(
            "A shape matrix must be exactly 6 finite numbers in DOMMatrix order [a, b, c, d, e, f], got {$count}. "
            .'Pass null to let canvas-confetti compute one, but note it does so by sampling a 1000x1000 grid, '
            .'so it is worth computing once and hard-coding the result.'
        );
    }

    public static function nonNumericMatrix(): self
    {
        return new self('A shape matrix must contain only finite numbers.');
    }

    public static function emptyText(): self
    {
        return new self('shapeFromText() needs a non-empty string, such as an emoji.');
    }
}
