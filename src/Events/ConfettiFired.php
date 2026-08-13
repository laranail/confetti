<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Events;

use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;

/**
 * Dispatched after a payload has been handed to a transport.
 */
final readonly class ConfettiFired
{
    public function __construct(
        public ConfettiPayload $payload,
        public string $transport,
    ) {}
}
