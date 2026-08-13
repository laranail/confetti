<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Enums;

use Simtabi\Laranail\Enumerator\Attributes\Description;
use Simtabi\Laranail\Enumerator\Attributes\Help;
use Simtabi\Laranail\Enumerator\Attributes\Label;
use Simtabi\Laranail\Enumerator\Concerns\HasEnumeratorBehavior;
use Simtabi\Laranail\Enumerator\Contracts\Enumerator;

/**
 * What to do for a visitor who has asked for reduced motion.
 *
 * This is the package's own gate, checked before every fire. It exists
 * separately from canvas-confetti's `disableForReducedMotion` option because
 * that option is evaluated once, when the library builds its cannon — so
 * toggling it per burst has no effect after the first one. We forward the
 * option for completeness but never depend on it.
 *
 * `Reduce` is the default: a full-screen effect is suppressed, but a single
 * small burst still acknowledges that something happened.
 */
#[Description('How the browser runtime responds to a prefers-reduced-motion preference.')]
enum ReducedMotionPolicy: string implements Enumerator
{
    use HasEnumeratorBehavior;

    #[Label('Ignore'), Help('Fire everything as normal. Only appropriate when confetti is the point of the page.')]
    case Ignore = 'ignore';

    #[Label('Reduce'), Help('Collapse animations to one short, half-count burst and drop trailing bursts.')]
    case Reduce = 'reduce';

    #[Label('Skip'), Help('Draw nothing at all.')]
    case Skip = 'skip';
}
