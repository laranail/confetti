<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Validation;

use Simtabi\Laranail\Confetti\Exceptions\InvalidColor;

/**
 * Validates and normalises confetti colours.
 *
 * canvas-confetti reads a colour by deleting every character that is not a hex
 * digit and interpreting the remainder, expanding it as shorthand if fewer than
 * six digits survive. That means invalid input never raises — it just paints
 * the wrong colour. `'red'` reduces to `'ed'`, expands, and produces something
 * unrelated; `'rgb(255, 0, 0)'` reduces to `'2550'` and does the same.
 *
 * Everything is normalised to lowercase `#rrggbb` so payloads dedupe cleanly
 * and comparisons in tests are not defeated by casing or a missing `#`.
 */
final class ColorValidator
{
    private const string HEX = '/^#?(?:[0-9a-f]{3}|[0-9a-f]{6})$/i';

    /**
     * @param list<mixed> $colors
     * @return list<string>
     *
     * @throws InvalidColor
     */
    public static function validateAll(array $colors, bool $strict = true): array
    {
        if ($colors === []) {
            if ($strict) {
                throw InvalidColor::emptyPalette();
            }

            return [];
        }

        $valid = [];

        foreach ($colors as $color) {
            $normalised = self::normalise($color);

            if ($normalised === null) {
                if ($strict) {
                    throw InvalidColor::notHex($color);
                }

                continue;
            }

            $valid[] = $normalised;
        }

        if ($valid === []) {
            if ($strict) {
                throw InvalidColor::emptyPalette();
            }

            return [];
        }

        return array_values(array_unique($valid));
    }

    /**
     * Normalise one colour to `#rrggbb`, or null when it is not valid hex.
     */
    public static function normalise(mixed $color): ?string
    {
        if (! is_string($color)) {
            return null;
        }

        $trimmed = trim($color);

        if (preg_match(self::HEX, $trimmed) !== 1) {
            return null;
        }

        $digits = strtolower(ltrim($trimmed, '#'));

        if (strlen($digits) === 3) {
            $digits = $digits[0].$digits[0].$digits[1].$digits[1].$digits[2].$digits[2];
        }

        return '#'.$digits;
    }

    public static function isValid(mixed $color): bool
    {
        return self::normalise($color) !== null;
    }
}
