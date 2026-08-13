<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Validation;

use Simtabi\Laranail\Confetti\Enums\ConfettiShape;
use Simtabi\Laranail\Confetti\Exceptions\InvalidShape;

/**
 * Validates particle shapes and the transform matrices attached to path shapes.
 */
final class ShapeValidator
{
    /**
     * Resolve a built-in shape name or enum case.
     *
     * @throws InvalidShape
     */
    public static function builtIn(ConfettiShape|string $shape, bool $strict = true): ?ConfettiShape
    {
        if ($shape instanceof ConfettiShape) {
            return $shape;
        }

        $resolved = ConfettiShape::coerce(strtolower(trim($shape)));

        if (! $resolved instanceof ConfettiShape && $strict) {
            throw InvalidShape::unknown($shape);
        }

        return $resolved;
    }

    /**
     * Validate a path shape's transform matrix.
     *
     * canvas-confetti indexes the matrix positionally as `[a, b, c, d, e, f]`,
     * the DOMMatrix component order. The DefinitelyTyped stubs describe this as
     * a `DOMMatrix` instance, which is wrong at runtime — the draw routine
     * guards on `Array.isArray`, so anything but a plain array of six numbers is
     * ignored and the shape draws untransformed.
     *
     * @param list<mixed>|null $matrix
     * @return list<float>|null
     *
     * @throws InvalidShape
     */
    public static function matrix(?array $matrix): ?array
    {
        if ($matrix === null || $matrix === []) {
            return null;
        }

        $values = array_values($matrix);

        if (count($values) !== 6) {
            throw InvalidShape::badMatrix(count($values));
        }

        $floats = [];

        foreach ($values as $value) {
            if (! is_int($value) && ! is_float($value)) {
                throw InvalidShape::nonNumericMatrix();
            }

            if (is_float($value) && ! is_finite($value)) {
                throw InvalidShape::nonNumericMatrix();
            }

            $floats[] = (float) $value;
        }

        return $floats;
    }

    /** @throws InvalidShape */
    public static function path(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '') {
            throw InvalidShape::emptyPath();
        }

        return $trimmed;
    }

    /** @throws InvalidShape */
    public static function text(string $text): string
    {
        if (trim($text) === '') {
            throw InvalidShape::emptyText();
        }

        return $text;
    }
}
