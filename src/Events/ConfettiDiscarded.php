<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Events;

use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;

/**
 * Dispatched when a payload was built but had nowhere to go.
 *
 * Usually means confetti was fired from a console command or a queued job.
 * Listen for it if that would indicate a bug in your application; ignore it
 * otherwise.
 */
final readonly class ConfettiDiscarded
{
    public function __construct(
        public ConfettiPayload $payload,
        public string $reason,
    ) {}
}
