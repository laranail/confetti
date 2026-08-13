<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Transport;

use Simtabi\Laranail\Confetti\Contracts\Transport;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;

/**
 * Records payloads instead of sending them.
 *
 * Backs `Confetti::fake()`, so a test can assert on what an action fired
 * without rendering a page.
 */
final class ArrayTransport implements Transport
{
    /** @var list<ConfettiPayload> */
    private array $payloads = [];

    public function name(): string
    {
        return 'array';
    }

    public function available(): bool
    {
        return true;
    }

    public function send(ConfettiPayload $payload): void
    {
        $this->payloads[] = $payload;
    }

    /** @return list<ConfettiPayload> */
    public function payloads(): array
    {
        return $this->payloads;
    }

    public function count(): int
    {
        return count($this->payloads);
    }

    public function flush(): void
    {
        $this->payloads = [];
    }
}
