<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Events;

/**
 * Dispatched when the confetti markup is written into a response.
 *
 * Fires once per page that carries the runtime, whether the markup came from
 * the Blade component, the auto-inject middleware or a Filament panel. It says
 * a page can show confetti, not that any was fired.
 *
 * Useful for answering "why did nothing appear" from the server side: no
 * `ConfettiRendered` means the runtime never reached the page, which is a
 * different problem from a payload that never arrived.
 */
final readonly class ConfettiRendered
{
    public function __construct(
        public string $source,
        public bool $hasPayload,
    ) {}
}
