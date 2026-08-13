# Custom shapes

Beyond `square`, `circle` and `star`, canvas-confetti draws particles from an
SVG path or from text.

## From text

```php
Confetti::make()
    ->shapeFromText('🎉')
    ->count(60)
    ->scalar(2)
    ->shoot();
```

Leave the scalar off the shape and it inherits the burst's. Keep it that way
unless you have a reason: the glyph is rasterised once at `10 × scalar` pixels
and then scaled by the burst's scalar when drawn, so a mismatch renders blurred
and at the wrong size.

Colour and font family are available for non-emoji text:

```php
Confetti::shapeFromText('★', scalar: 1.5, color: '#ffe400', fontFamily: 'Georgia, serif')->shoot();
```

The runtime caches the rasterised bitmap, so a long animation does not allocate
one per frame.

## From a path

```php
$heart = 'M167 72c19,-38 37,-56 75,-56 42,0 76,33 76,75 0,76 -76,151 -151,227 -76,-76 -151,-151 -151,-227 0,-42 33,-75 75,-75 38,0 57,18 76,56z';

Confetti::make()
    ->shapeFromPath($heart, [0.02, 0, 0, 0.02, -3.5, -4])
    ->colors('#f93963', '#a10864', '#ee0b93')
    ->shoot();
```

The matrix is six numbers in DOMMatrix order, as a plain array. The
DefinitelyTyped stubs say `DOMMatrix`, which is wrong at runtime.

Passing `null` lets canvas-confetti work one out, which it does by sampling a
1000×1000 grid with `isPointInPath`, in the browser, on the main thread. Fine
once; compute it and hard-code it for anything that fires often. The runtime
warns once per path when you have not.

## Mixing

Shapes are picked per particle, so repeating one weights it:

```php
Confetti::shapes('circle', 'circle', 'star')->shoot();   // roughly 2:1
```

## When the browser cannot

`shapeFromText` needs `OffscreenCanvas` and `shapeFromPath` needs `Path2D`.
Where either is missing the runtime reports it and the burst falls back to the
default shapes rather than failing.

See [Validation](../tools/validation.md).

---

[← Docs index](../../README.md#documentation)
