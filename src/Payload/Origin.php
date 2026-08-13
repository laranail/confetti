<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Payload;

/**
 * A launch point, in viewport-relative coordinates.
 *
 * `0,0` is the top-left corner and `1,1` the bottom-right, but values outside
 * that box are legitimate and used on purpose: the upstream fireworks and snow
 * recipes both launch from a negative `y` so particles drift in from above the
 * fold. Nothing here clamps.
 */
final readonly class Origin
{
    public function __construct(
        public float $x = 0.5,
        public float $y = 0.5,
    ) {}

    /** @param array{x?: float|int, y?: float|int} $origin */
    public static function fromArray(array $origin): self
    {
        return new self(
            x: (float) ($origin['x'] ?? 0.5),
            y: (float) ($origin['y'] ?? 0.5),
        );
    }

    /** @return array{x: float, y: float} */
    public function toArray(): array
    {
        return ['x' => $this->x, 'y' => $this->y];
    }
}
