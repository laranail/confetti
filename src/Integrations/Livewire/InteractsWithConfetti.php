<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Integrations\Livewire;

use Simtabi\Laranail\Confetti\Builder\ConfettiBuilder;
use Simtabi\Laranail\Confetti\Confetti;

/**
 * Fires confetti from a Livewire component.
 *
 *     class Checkout extends Component
 *     {
 *         use InteractsWithConfetti;
 *
 *         public function complete(): void
 *         {
 *             $this->order->complete();
 *             $this->confetti()->realistic()->shoot();
 *         }
 *     }
 *
 * Strictly a convenience: `Confetti::realistic()->shoot()` works identically
 * inside a component, because the transport detects the Livewire request on its
 * own. The trait exists to make the intent obvious at the call site and to give
 * a component a single place to override how its confetti is built.
 */
trait InteractsWithConfetti
{
    protected function confetti(): ConfettiBuilder
    {
        return app(Confetti::class)->make();
    }
}
