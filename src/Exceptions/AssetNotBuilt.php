<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Exceptions;

use RuntimeException;

/**
 * The browser bundle is missing from the package.
 *
 * Deliberately never thrown while rendering a page — the asset route answers
 * with a 404 carrying an explanatory comment instead, so a missing bundle costs
 * a page its confetti rather than returning a 500. This exception is for the
 * doctor command and the install command, where failing loudly is the point.
 */
final class AssetNotBuilt extends RuntimeException implements ConfettiException
{
    public static function at(string $path): self
    {
        return new self(
            "The confetti browser bundle is missing at {$path}. "
            .'It ships with the package, so this normally means an incomplete checkout — '
            .'run `npm install && npm run build` in the package directory to rebuild it.'
        );
    }
}
