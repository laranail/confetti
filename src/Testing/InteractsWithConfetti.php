<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Testing;

use Simtabi\Laranail\Confetti\Facades\Confetti;

/**
 * Convenience wrapper for test cases that assert on confetti.
 *
 *     uses(InteractsWithConfetti::class);
 *
 *     it('celebrates a completed order', function () {
 *         $this->fakeConfetti();
 *         $this->post('/orders');
 *         $this->confetti()->assertAnimation('fireworks');
 *     });
 */
trait InteractsWithConfetti
{
    protected ?ConfettiFake $confettiFake = null;

    protected function fakeConfetti(): ConfettiFake
    {
        return $this->confettiFake = Confetti::fake();
    }

    protected function confetti(): ConfettiFake
    {
        return $this->confettiFake ?? $this->fakeConfetti();
    }
}
