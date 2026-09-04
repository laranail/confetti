<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Enums\ConfettiAnimation;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;

beforeEach(fn () => Confetti::fake());

it('records what was fired instead of sending it', function (): void {
    Confetti::realistic()->shoot();

    Confetti::assertFired();
    Confetti::assertFiredTimes(1);
    Confetti::assertBurstCount(5);
});

it('matches a payload against a predicate', function (): void {
    Confetti::count(42)->shoot();

    Confetti::assertFired(fn (ConfettiPayload $p): bool => $p->bursts[0]->options['particleCount'] === 42);
});

it('asserts on a continuous effect by name', function (): void {
    Confetti::snow()->shoot();

    Confetti::assertAnimation(ConfettiAnimation::Snow);
    Confetti::assertAnimation('snow');
});

it('asserts that nothing fired', function (): void {
    Confetti::assertNothingFired();
});

it('names what actually fired when an assertion fails', function (): void {
    // A failure message of "expected confetti, got none" is slow to debug when
    // the effect is three layers down in a controller.
    Confetti::stars()->shoot();

    expect(fn () => Confetti::assertAnimation('snow'))
        ->toThrow(AssertionFailedError::class, 'no animation was fired');

    expect(fn () => Confetti::assertNothingFired())
        ->toThrow(AssertionFailedError::class, '6 burst(s)');

    expect(fn () => Confetti::assertFiredTimes(3))
        ->toThrow(AssertionFailedError::class, 'fired 1 time(s)');
});

it('refuses to assert before fake() is called', function (): void {
    Confetti::restore();

    expect(fn () => Confetti::assertFired())
        ->toThrow(RuntimeException::class, 'Call Confetti::fake()');
});
