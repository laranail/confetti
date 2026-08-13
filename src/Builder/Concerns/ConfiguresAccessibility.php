<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Builder\Concerns;

use Simtabi\Laranail\Confetti\Enums\ReducedMotionPolicy;

/**
 * How this effect treats a visitor who has asked for reduced motion.
 *
 * There are two mechanisms and they are not interchangeable.
 *
 * {@see disableForReducedMotion()} sets canvas-confetti's own option. It works,
 * but only once: the library evaluates the media query when it builds its
 * cannon and caches the answer, so setting the option on a later burst has no
 * effect. It is forwarded for completeness.
 *
 * {@see reducedMotion()} sets the package's policy, which the runtime re-checks
 * before every fire. That is the one to reach for, and it offers a middle
 * option — `Reduce` keeps a brief acknowledgement instead of choosing between a
 * full fifteen-second snowfall and nothing at all.
 */
trait ConfiguresAccessibility
{
    /** Forward canvas-confetti's own reduced-motion flag. */
    public function disableForReducedMotion(bool $disable = true): static
    {
        return $this->setOption('disableForReducedMotion', $disable);
    }

    /** Set the package's reduced-motion policy for this effect. */
    public function reducedMotion(ReducedMotionPolicy|string $policy): static
    {
        $this->reducedMotion = $policy instanceof ReducedMotionPolicy
            ? $policy
            : (ReducedMotionPolicy::coerce($policy) ?? ReducedMotionPolicy::Reduce);

        return $this;
    }

    /** Draw nothing for visitors who prefer reduced motion. */
    public function skipForReducedMotion(): static
    {
        return $this->reducedMotion(ReducedMotionPolicy::Skip);
    }
}
