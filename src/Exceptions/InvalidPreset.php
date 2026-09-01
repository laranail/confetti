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
            .'Register your own with Confetti::registerPreset().',
        );
    }

    /**
     * A `kind:` in the preset enum that nothing downstream knows how to carry.
     *
     * Only reachable by editing ConfettiPreset, so the message is aimed at
     * whoever is adding a case rather than at an application developer.
     */
    public static function unknownKind(string $preset, mixed $kind): self
    {
        $shown = is_scalar($kind) ? var_export($kind, true) : get_debug_type($kind);

        return new self(
            "Confetti preset '{$preset}' declares kind {$shown}, which is not one of "
            ."'options', 'burst' or 'animation'. Fix the Meta attribute on that case "
            .'in Enums\ConfettiPreset.',
        );
    }
}
