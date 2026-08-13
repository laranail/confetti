<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Payload;

/**
 * Everything fired during the current request, held in memory.
 *
 * This exists to fix a specific bug, and the fix is entirely in what it does
 * *not* do. The obvious way to let several `shoot()` calls accumulate in a
 * session flash is to read the existing value and merge:
 *
 *     session()->flash($key, array_merge(session()->get($key, []), $new));
 *
 * That is wrong, because `session()->get()` on a flashed key returns the data
 * flashed by the *previous* request: the payload currently being rendered.
 * Re-flashing it extends its life by another request, so the same confetti fires
 * again on the next page, and again on the one after that, for as long as
 * anything keeps firing.
 *
 * Accumulating here instead means the session is only ever written to, never
 * read back, so nothing inbound can survive into the next request.
 *
 * Bound with `scoped()` rather than `singleton()`. Under Octane a singleton
 * outlives the request, and payloads would leak between visitors, which is the
 * same bug wearing a different hat.
 */
final class PendingBursts
{
    private ?ConfettiPayload $payload = null;

    public function push(ConfettiPayload $payload): void
    {
        $this->payload = $this->payload?->merge($payload) ?? $payload;
    }

    public function payload(): ?ConfettiPayload
    {
        return $this->payload;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->payload?->toArray() ?? [];
    }

    public function isEmpty(): bool
    {
        return ! $this->payload instanceof ConfettiPayload;
    }

    public function flush(): void
    {
        $this->payload = null;
    }
}
