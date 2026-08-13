<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Builder\Concerns;

use Simtabi\Laranail\Confetti\Enums\ConfettiPosition;

/**
 * Where a burst launches from.
 *
 * Coordinates are viewport-relative: `0,0` is the top-left, `1,1` the
 * bottom-right. Values outside that range are allowed and useful: launching
 * from `y: -0.2` puts the origin just above the fold so particles fall into
 * view, which is how the fireworks and snow effects work.
 *
 * The named shortcuts each set an origin *and* the angle that fires away from
 * the nearest edge, so `bottomLeft()` sprays up and to the right rather than
 * off-screen.
 */
trait ConfiguresOrigin
{
    /** Launch from an explicit point. */
    public function origin(float $x, float $y): static
    {
        return $this->setOption('origin', $this->validator->origin($x, $y));
    }

    /** Move the origin horizontally, keeping the current vertical position. */
    public function originX(float $x): static
    {
        $current = $this->currentOrigin();

        return $this->origin($x, $current['y']);
    }

    /** Move the origin vertically, keeping the current horizontal position. */
    public function originY(float $y): static
    {
        $current = $this->currentOrigin();

        return $this->origin($current['x'], $y);
    }

    /**
     * Launch from a named position.
     *
     * Sets the angle too, except for `center`, which leaves whatever angle is
     * already in play so a centred burst can still be aimed.
     */
    public function position(ConfettiPosition|string $position): static
    {
        $resolved = $position instanceof ConfettiPosition
            ? $position
            : (ConfettiPosition::coerce($position) ?? ConfettiPosition::Center);

        $this->origin($resolved->x(), $resolved->y());

        $angle = $resolved->angle();

        return $angle === null ? $this : $this->angle($angle);
    }

    public function center(): static
    {
        return $this->position(ConfettiPosition::Center);
    }

    public function top(): static
    {
        return $this->position(ConfettiPosition::Top);
    }

    public function bottom(): static
    {
        return $this->position(ConfettiPosition::Bottom);
    }

    public function left(): static
    {
        return $this->position(ConfettiPosition::Left);
    }

    public function right(): static
    {
        return $this->position(ConfettiPosition::Right);
    }

    public function topLeft(): static
    {
        return $this->position(ConfettiPosition::TopLeft);
    }

    public function topRight(): static
    {
        return $this->position(ConfettiPosition::TopRight);
    }

    public function bottomLeft(): static
    {
        return $this->position(ConfettiPosition::BottomLeft);
    }

    public function bottomRight(): static
    {
        return $this->position(ConfettiPosition::BottomRight);
    }

    /** @return array{x: float, y: float} */
    private function currentOrigin(): array
    {
        $origin = $this->stack->get('origin');

        if (is_array($origin)) {
            return [
                'x' => (float) ($origin['x'] ?? 0.5),
                'y' => (float) ($origin['y'] ?? 0.5),
            ];
        }

        return ['x' => 0.5, 'y' => 0.5];
    }
}
