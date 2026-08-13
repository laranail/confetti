<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Exceptions;

use InvalidArgumentException;

/**
 * A named effect that is not configured, or is configured wrongly.
 */
final class InvalidEffect extends InvalidArgumentException implements ConfettiException
{
    /** @param list<string> $available */
    public static function unknown(string $name, array $available): self
    {
        $list = $available === []
            ? 'none are configured'
            : "'".implode("', '", $available)."'";

        return new self(
            "Unknown confetti effect '{$name}'. Configured effects: {$list}. "
            .'Add one under laranail.confetti.effects, or register it at runtime '
            .'with Confetti::registerEffect().'
        );
    }

    public static function unknownOption(string $effect, string $option): self
    {
        return new self(
            "Confetti effect '{$effect}' sets '{$option}', which is not a builder method. "
            .'Each key in an effect names a method on the builder, so use the method name: '
            ."'count' rather than 'particleCount', 'palette' rather than 'colours'."
        );
    }
}
