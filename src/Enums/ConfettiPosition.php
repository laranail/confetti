<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Enums;

use Simtabi\Laranail\Enumerator\Attributes\Description;
use Simtabi\Laranail\Enumerator\Attributes\Label;
use Simtabi\Laranail\Enumerator\Attributes\Meta;
use Simtabi\Laranail\Enumerator\Concerns\HasEnumeratorBehavior;
use Simtabi\Laranail\Enumerator\Contracts\Enumerator;

/**
 * Named launch points, each pairing a viewport origin with the angle that fires
 * confetti away from the nearest edge.
 *
 * Origin runs `0..1` across the viewport: `x: 0` is the left edge, `y: 0` the
 * top. Angles are canvas-confetti's: 90° is straight up, 0° is to the right,
 * and they increase anticlockwise. So `Top` sits at `y: 0` and fires *downward*
 * at 270°, which reads backwards until you remember the origin is the corner
 * the particles are launched from.
 *
 * `Center` deliberately carries a null angle so it inherits whatever the
 * builder or preset set, rather than snapping bursts back to the default 90°.
 */
#[Description('A named viewport origin paired with the angle that fires away from the nearest edge.')]
enum ConfettiPosition: string implements Enumerator
{
    use HasEnumeratorBehavior;

    #[Label('Center'),       Meta(x: 0.5, y: 0.5, angle: null)] case Center = 'center';
    #[Label('Top'),          Meta(x: 0.5, y: 0.0, angle: 270)] case Top = 'top';
    #[Label('Bottom'),       Meta(x: 0.5, y: 1.0, angle: 90)] case Bottom = 'bottom';
    #[Label('Left'),         Meta(x: 0.0, y: 0.5, angle: 0)] case Left = 'left';
    #[Label('Right'),        Meta(x: 1.0, y: 0.5, angle: 180)] case Right = 'right';
    #[Label('Top left'),     Meta(x: 0.0, y: 0.0, angle: 315)] case TopLeft = 'top-left';
    #[Label('Top right'),    Meta(x: 1.0, y: 0.0, angle: 225)] case TopRight = 'top-right';
    #[Label('Bottom left'),  Meta(x: 0.0, y: 1.0, angle: 60)] case BottomLeft = 'bottom-left';
    #[Label('Bottom right'), Meta(x: 1.0, y: 1.0, angle: 120)] case BottomRight = 'bottom-right';

    public function x(): float
    {
        return (float) $this->meta('x');
    }

    public function y(): float
    {
        return (float) $this->meta('y');
    }

    /**
     * The firing angle in degrees, or null when this position leaves the angle
     * to whatever the builder already set.
     */
    public function angle(): ?int
    {
        $angle = $this->meta('angle');

        return $angle === null ? null : (int) $angle;
    }
}
