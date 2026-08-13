<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Contracts;

/**
 * One entry in a burst's `shapes` array.
 *
 * Built-in shapes serialise to a bare string; path and text shapes serialise to
 * a descriptor object that the browser runtime rehydrates through
 * `confetti.shapeFromPath()` or `confetti.shapeFromText()`. PHP cannot build
 * either of those — one needs `Path2D`, the other an `OffscreenCanvas` — so the
 * wire format carries the instructions rather than the result.
 */
interface Shape
{
    /**
     * The wire representation: a string for built-ins, an array for descriptors.
     *
     * @return array<string, mixed>|string
     */
    public function toWire(): array|string;
}
