<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Events;

use Simtabi\Laranail\Confetti\Confetti;
use Simtabi\Laranail\Confetti\Builder\ConfettiBuilder;

/**
 * Dispatched when a builder is created, before anything has been set on it.
 *
 * The builder is mutable and is passed by reference, so a listener can apply
 * policy to every effect in the application:
 *
 *     Event::listen(ConfettiPreparing::class, function (ConfettiPreparing $event) {
 *         if (auth()->user()?->prefersCalm()) {
 *             $event->builder->reducedMotion('skip');
 *         }
 *     });
 *
 * {@see Confetti::before()} does the same thing
 * without the event dispatcher, and runs first. Reach for the event when the
 * listener belongs somewhere other than a service provider.
 */
final readonly class ConfettiPreparing
{
    public function __construct(
        public ConfettiBuilder $builder,
    ) {}
}
