<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Payload\Shapes;

use Simtabi\Laranail\Confetti\Contracts\Shape;

/**
 * A particle drawn from text — in practice, an emoji.
 *
 * `scalar` deliberately defaults to null, meaning "inherit the burst's own
 * `scalar`". This matters more than it looks: canvas-confetti rasterises the
 * text once, at `10 * scalar` pixels, and then scales the resulting bitmap by
 * the burst's `scalar` when drawing. Supply different values to the two and the
 * particle is drawn at the wrong size and visibly blurred. Inheriting by
 * default makes the two agree unless someone deliberately separates them.
 *
 * The wire `type` stays `"text"` even though the object canvas-confetti builds
 * reports itself as `"bitmap"` — the payload describes what to construct, not
 * what comes out.
 */
final readonly class TextShape implements Shape
{
    public function __construct(
        public string $text,
        public ?float $scalar = null,
        public string $color = '#000000',
        public ?string $fontFamily = null,
    ) {}

    /** Resolve the inherited scalar against a burst's own value. */
    public function withInheritedScalar(float $scalar): self
    {
        return $this->scalar !== null
            ? $this
            : new self($this->text, $scalar, $this->color, $this->fontFamily);
    }

    public function toWire(): array
    {
        return array_filter([
            'type' => 'text',
            'text' => $this->text,
            'scalar' => $this->scalar,
            'color' => $this->color,
            'fontFamily' => $this->fontFamily,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
