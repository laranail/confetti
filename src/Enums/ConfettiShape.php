<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Enums;

use Simtabi\Laranail\Enumerator\Attributes\Label;
use Simtabi\Laranail\Enumerator\Contracts\Enumerator;
use Simtabi\Laranail\Enumerator\Attributes\Description;
use Simtabi\Laranail\Confetti\Validation\ShapeValidator;
use Simtabi\Laranail\Enumerator\Concerns\HasEnumeratorBehavior;

/**
 * The particle shapes canvas-confetti draws natively.
 *
 * There are exactly three, and the list is closed: the library's draw routine
 * branches on `circle` and `star` and treats *everything else* as a square. An
 * unrecognised string therefore renders silently rather than failing, which is
 * why {@see ShapeValidator} rejects
 * anything outside this enum instead of passing it through.
 *
 * For shapes beyond these three, use `shapeFromPath()` or `shapeFromText()`.
 */
#[Description('A particle shape canvas-confetti can draw without a custom path or bitmap.')]
enum ConfettiShape: string implements Enumerator
{
    use HasEnumeratorBehavior;

    #[Label('Square')] case Square = 'square';
    #[Label('Circle')] case Circle = 'circle';
    #[Label('Star')] case Star = 'star';
}
