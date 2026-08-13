<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Exceptions;

use RuntimeException;

/**
 * A transport was asked for explicitly but cannot run in this context.
 *
 * Only raised for a driver named through `via()` or configuration. Automatic
 * resolution never throws — it falls through to the null transport, because
 * confetti is decorative and should not be able to break a console command or
 * a queued job.
 */
final class TransportUnavailable extends RuntimeException implements ConfettiException
{
    public static function driver(string $driver, string $reason): self
    {
        return new self("The '{$driver}' confetti transport is unavailable: {$reason}.");
    }

    public static function unknown(string $driver, array $available): self
    {
        $list = implode("', '", $available);

        return new self("Unknown confetti transport '{$driver}'. Available drivers: '{$list}'.");
    }
}
