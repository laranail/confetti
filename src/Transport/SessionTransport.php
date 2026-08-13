<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Transport;

use Illuminate\Contracts\Session\Session;
use Simtabi\Laranail\Confetti\Contracts\Transport;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;
use Simtabi\Laranail\Confetti\Payload\PendingBursts;

/**
 * Flashes the payload so it renders on the next response.
 *
 * The default for ordinary requests, and the one that makes
 * `redirect()->back()` followed by confetti work at all.
 *
 * **This class never reads the session.** That is the whole fix for a bug worth
 * describing, because the obvious implementation has it. To let several
 * `shoot()` calls accumulate you want to merge with what is already there:
 *
 *     session()->flash($key, array_merge(session()->get($key, []), $new));
 *
 * But on a flashed key, `get()` returns what the *previous* request flashed —
 * the payload currently being rendered. Merging it into the new flash extends
 * its life by another request, so the same confetti fires again on the next
 * page, and the one after that, indefinitely. It reads as "confetti is stuck
 * on" and is maddening to trace, because the code that fires it ran once.
 *
 * Accumulation happens in {@see PendingBursts} instead, which lives and dies
 * with the request.
 */
final readonly class SessionTransport implements Transport
{
    public function __construct(
        private Session $session,
        private PendingBursts $pending,
        private string $key,
    ) {}

    public function name(): string
    {
        return 'session';
    }

    public function available(): bool
    {
        return $this->session->isStarted();
    }

    public function send(ConfettiPayload $payload): void
    {
        $this->pending->push($payload);

        // Write only. Reading the key back would resurrect the previous
        // request's payload — see the class docblock.
        $this->session->flash($this->key, $this->pending->toArray());
    }
}
