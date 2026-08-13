<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Payload\Shapes;

use Simtabi\Laranail\Confetti\Contracts\Shape;

/**
 * A particle drawn from an SVG path.
 *
 * The matrix is optional. Leaving it out is convenient and expensive: to work
 * one out, canvas-confetti walks a 1000x1000 grid calling `isPointInPath` every
 * two pixels, then scales the result. That happens in the browser, on the main
 * thread, the first time the shape is used. Compute it once during development
 * and pass it explicitly for anything that fires often.
 *
 * The matrix is a plain array of six numbers in DOMMatrix order. The
 * DefinitelyTyped stubs type it as a `DOMMatrix` instance, but the drawing code
 * guards on `Array.isArray`, so an object is ignored and the shape renders
 * untransformed.
 */
final readonly class PathShape implements Shape
{
    /** @param list<float>|null $matrix */
    public function __construct(
        public string $path,
        public ?array $matrix = null,
    ) {}

    public function toWire(): array
    {
        return [
            'type' => 'path',
            'path' => $this->path,
            'matrix' => $this->matrix,
        ];
    }
}
