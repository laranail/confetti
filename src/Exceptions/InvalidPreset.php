<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Exceptions;

use InvalidArgumentException;

/**
 * A preset the registry does not know about.
 */
final class InvalidPreset extends InvalidArgumentException implements ConfettiException
{
    /** @param list<string> $available */
    public static function unknown(string $name, array $available): self
    {
        $list = implode("', '", $available);

        return new self(
            "Unknown confetti preset '{$name}'. Registered presets: '{$list}'. "
            .'Register your own with Confetti::registerPreset().'
        );
    }

    public static function notExpandable(string $name): self
    {
        return new self(
            "Preset '{$name}' has nothing to expand — expand() applies to continuous effects "
            .'(fireworks, snow, schoolPride), which otherwise run as an animation loop in the browser.'
        );
    }
}
