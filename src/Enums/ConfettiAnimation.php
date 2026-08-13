<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Enums;

use Simtabi\Laranail\Enumerator\Attributes\Description;
use Simtabi\Laranail\Enumerator\Attributes\Label;
use Simtabi\Laranail\Enumerator\Attributes\Meta;
use Simtabi\Laranail\Enumerator\Concerns\HasEnumeratorBehavior;
use Simtabi\Laranail\Enumerator\Contracts\Enumerator;

/**
 * The continuous effects that run as a client-side animation loop rather than a
 * fixed list of bursts.
 *
 * These three are the canvas-confetti recipes built on `requestAnimationFrame`
 * or `setInterval`, so they emit particles for a duration rather than all at
 * once. Expanding them server-side would mean serialising hundreds of shots
 * with the randomness already resolved — every visitor seeing identical
 * "random" snow. Instead the payload carries a descriptor and the browser runs
 * the loop.
 *
 * `defaultDuration` is in milliseconds and can be overridden per call.
 */
#[Description('A continuous confetti effect executed by a requestAnimationFrame loop in the browser.')]
enum ConfettiAnimation: string implements Enumerator
{
    use HasEnumeratorBehavior;

    #[Label('Fireworks'),    Meta(defaultDuration: 15000)] case Fireworks = 'fireworks';
    #[Label('Snow'),         Meta(defaultDuration: 15000)] case Snow = 'snow';
    #[Label('School pride'), Meta(defaultDuration: 15000)] case SchoolPride = 'schoolPride';

    public function defaultDuration(): int
    {
        return (int) $this->meta('defaultDuration');
    }
}
