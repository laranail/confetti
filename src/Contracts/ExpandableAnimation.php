<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Contracts;

use Simtabi\Laranail\Confetti\Payload\Animation;
use Simtabi\Laranail\Confetti\Payload\Burst;
use Simtabi\Laranail\Confetti\Support\Seed;

/**
 * A continuous effect that can also be walked out in PHP.
 *
 * Implemented by the animation presets so `->expand()` has something to call.
 * The expansion runs the same loop the browser would, drawing every random
 * value from the supplied {@see Seed} so the output is reproducible, which is
 * the only reason to expand at all, since the result is orders of magnitude
 * larger on the wire than the descriptor it replaces.
 */
interface ExpandableAnimation
{
    /**
     * Walk the animation loop and return the bursts it would have produced.
     *
     * @return list<Burst>
     */
    public function expand(Animation $animation, Seed $seed): array;
}
