<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Builder\Concerns;

use Simtabi\Laranail\Confetti\Contracts\Shape;
use Simtabi\Laranail\Confetti\Enums\ConfettiShape;
use Simtabi\Laranail\Confetti\Payload\Shapes\BuiltInShape;
use Simtabi\Laranail\Confetti\Payload\Shapes\PathShape;
use Simtabi\Laranail\Confetti\Payload\Shapes\TextShape;
use Simtabi\Laranail\Confetti\Validation\ColorValidator;
use Simtabi\Laranail\Confetti\Validation\ShapeValidator;

/**
 * What the particles look like.
 *
 * Colours must be hex. canvas-confetti parses a colour by deleting every
 * non-hex character and reading the remainder, so `'red'` and `'rgb(255,0,0)'`
 * do not fail — they paint something arbitrary. Validating here turns a silent
 * wrong colour into an error.
 *
 * Shapes are picked per particle from the list, so repeating an entry weights
 * it: `shapes('circle', 'circle', 'star')` gives roughly two circles per star.
 */
trait ConfiguresAppearance
{
    /**
     * Set the palette. Accepts hex strings variadically or as a single array.
     *
     * @param string|list<string> ...$colors
     */
    public function colors(string|array ...$colors): static
    {
        $flat = [];

        foreach ($colors as $color) {
            if (is_array($color)) {
                $flat = [...$flat, ...array_values($color)];

                continue;
            }

            $flat[] = $color;
        }

        return $this->setOption('colors', ColorValidator::validateAll($flat, $this->validator->isStrict()));
    }

    /** Use a palette named in `laranail.confetti.palettes`. */
    public function palette(string $name): static
    {
        return $this->setOption('colors', $this->config->palette($name));
    }

    /**
     * Set the particle shapes.
     *
     * @param ConfettiShape|Shape|string|list<ConfettiShape|Shape|string> ...$shapes
     */
    public function shapes(ConfettiShape|Shape|string|array ...$shapes): static
    {
        $flat = [];

        foreach ($shapes as $shape) {
            if (is_array($shape)) {
                $flat = [...$flat, ...array_values($shape)];

                continue;
            }

            $flat[] = $shape;
        }

        $resolved = [];

        foreach ($flat as $shape) {
            if ($shape instanceof Shape) {
                $resolved[] = $shape;

                continue;
            }

            $builtIn = ShapeValidator::builtIn($shape, $this->validator->isStrict());

            if ($builtIn instanceof ConfettiShape) {
                $resolved[] = BuiltInShape::of($builtIn);
            }
        }

        return $this->setOption('shapes', $resolved);
    }

    /**
     * Add a particle drawn from an SVG path.
     *
     * Passing a matrix is optional but worth doing for anything that fires
     * often: without one, canvas-confetti works out the transform by sampling a
     * 1000x1000 grid in the browser, on the main thread, the first time the
     * shape is used. Compute it once and hard-code it.
     *
     * @param list<float>|null $matrix Six numbers in DOMMatrix order.
     */
    public function shapeFromPath(string $path, ?array $matrix = null): static
    {
        return $this->addShape(new PathShape(
            path: ShapeValidator::path($path),
            matrix: ShapeValidator::matrix($matrix),
        ));
    }

    /**
     * Add a particle drawn from text — usually an emoji.
     *
     * Leaving `$scalar` null makes the glyph inherit the burst's own `scalar`,
     * which is almost always what you want: canvas-confetti rasterises the text
     * once at `10 * scalar` pixels and then scales the bitmap by the burst's
     * scalar when drawing, so a mismatch between the two renders blurred and at
     * the wrong size.
     */
    public function shapeFromText(
        string $text,
        ?float $scalar = null,
        string $color = '#000000',
        ?string $fontFamily = null,
    ): static {
        return $this->addShape(new TextShape(
            text: ShapeValidator::text($text),
            scalar: $scalar,
            color: ColorValidator::normalise($color) ?? '#000000',
            fontFamily: $fontFamily,
        ));
    }

    /** Append one shape to whatever is already set. */
    private function addShape(Shape $shape): static
    {
        $current = $this->stack->get('shapes');
        $shapes = is_array($current) ? array_values($current) : [];
        $shapes[] = $shape;

        return $this->setOption('shapes', $shapes);
    }
}
