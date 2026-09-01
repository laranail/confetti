<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Testing;

use PHPUnit\Framework\Assert;
use Simtabi\Laranail\Confetti\Enums\ConfettiAnimation;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;
use Simtabi\Laranail\Confetti\Transport\ArrayTransport;

/**
 * Assertions over the confetti an action fired.
 *
 *     Confetti::fake();
 *
 *     $this->post('/orders', $payload);
 *
 *     Confetti::assertFiredTimes(1);
 *     Confetti::assertFired(fn ($p) => $p->burstCount() === 5);
 *
 * Failure messages name what was actually recorded, because "expected confetti,
 * got none" is a slow thing to debug when the effect is three layers down in a
 * controller.
 */
final readonly class ConfettiFake
{
    public function __construct(
        private ArrayTransport $recorder,
    ) {}

    /** @return list<ConfettiPayload> */
    public function payloads(): array
    {
        return $this->recorder->payloads();
    }

    /**
     * Assert confetti fired, optionally matching a predicate.
     *
     * @param  null|callable(ConfettiPayload): bool  $callback
     */
    public function assertFired(?callable $callback = null): void
    {
        $payloads = $this->payloads();

        Assert::assertNotEmpty($payloads, 'Expected confetti to have been fired, but none was.');

        if ($callback === null) {
            return;
        }

        $matched = array_filter($payloads, $callback);

        Assert::assertNotEmpty(
            $matched,
            sprintf(
                'Expected confetti matching the given condition, but none of the %d fired payload(s) matched. Fired: %s',
                count($payloads),
                $this->summarise($payloads),
            ),
        );
    }

    public function assertFiredTimes(int $times): void
    {
        $actual = count($this->payloads());

        Assert::assertSame(
            $times,
            $actual,
            sprintf('Expected confetti to have been fired %d time(s), but it fired %d time(s).', $times, $actual),
        );
    }

    public function assertNothingFired(): void
    {
        $payloads = $this->payloads();

        Assert::assertEmpty(
            $payloads,
            sprintf('Expected no confetti to have been fired, but %s fired.', $this->summarise($payloads)),
        );
    }

    /** Assert a specific continuous effect was requested. */
    public function assertAnimation(ConfettiAnimation|string $animation): void
    {
        $name = $animation instanceof ConfettiAnimation ? $animation->value : $animation;

        $found = [];

        foreach ($this->payloads() as $payload) {
            foreach ($payload->animations as $candidate) {
                $found[] = $candidate->animation->value;
            }
        }

        Assert::assertContains(
            $name,
            $found,
            $found === []
                ? "Expected the '{$name}' animation, but no animation was fired."
                : sprintf("Expected the '%s' animation, but only found: %s.", $name, implode(', ', $found)),
        );
    }

    /** Assert the total number of bursts across every fired payload. */
    public function assertBurstCount(int $count): void
    {
        $actual = array_sum(array_map(
            static fn (ConfettiPayload $payload): int => $payload->burstCount(),
            $this->payloads(),
        ));

        Assert::assertSame(
            $count,
            $actual,
            sprintf('Expected %d burst(s) in total, but found %d.', $count, $actual),
        );
    }

    public function flush(): void
    {
        $this->recorder->flush();
    }

    /** @param list<ConfettiPayload> $payloads */
    private function summarise(array $payloads): string
    {
        if ($payloads === []) {
            return 'nothing';
        }

        $parts = array_map(
            static function (ConfettiPayload $payload): string {
                $animations = array_map(
                    static fn (object $a): string => $a->animation->value,
                    $payload->animations,
                );

                return sprintf(
                    '%d burst(s)%s',
                    $payload->burstCount(),
                    $animations === [] ? '' : ' + '.implode('/', $animations),
                );
            },
            $payloads,
        );

        return implode('; ', $parts);
    }
}
