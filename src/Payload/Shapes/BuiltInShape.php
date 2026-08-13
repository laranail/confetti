<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Payload\Shapes;

use Simtabi\Laranail\Confetti\Contracts\Shape;
use Simtabi\Laranail\Confetti\Enums\ConfettiShape;

/**
 * One of the three shapes canvas-confetti draws without help.
 */
final readonly class BuiltInShape implements Shape
{
    public function __construct(
        public ConfettiShape $shape,
    ) {}

    public static function of(ConfettiShape $shape): self
    {
        return new self($shape);
    }

    public function toWire(): string
    {
        return $this->shape->value;
    }
}
