<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Transport;

use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\Confetti\Contracts\Transport;
use Simtabi\Laranail\Confetti\Events\ConfettiDiscarded;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;

/**
 * Drops the payload.
 *
 * Resolved whenever there is no browser to send to: a console command, a
 * queued job, a request without a session. Firing confetti from a scheduled
 * task is a mistake, but it is not a mistake worth crashing over, so this
 * discards quietly and dispatches {@see ConfettiDiscarded} for anyone who wants
 * to know.
 *
 * That silence is deliberate. The alternative, reaching for `session()`
 * unconditionally, is what made the previous implementation throw inside queue
 * workers.
 */
final readonly class NullTransport implements Transport
{
    public function __construct(
        private ?Dispatcher $events = null,
        private string $reason = 'No browser context is available for this request.',
    ) {}

    public function name(): string
    {
        return 'null';
    }

    public function available(): bool
    {
        return true;
    }

    public function send(ConfettiPayload $payload): void
    {
        $this->events?->dispatch(new ConfettiDiscarded($payload, $this->reason));
    }
}
