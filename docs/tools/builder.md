# The builder

`Simtabi\Laranail\Confetti\Builder\ConfettiBuilder`, reached through the
`Confetti` facade: 40-odd fluent methods describing an effect, and four ways to
inspect or send it.

Any method not on the facade is forwarded to a fresh builder, so
`Confetti::realistic()` and `Confetti::make()->realistic()` are the same thing.

## Particles

| Method | canvas-confetti option | Notes |
|---|---|---|
| `count(int)` | `particleCount` | 0-1000 by default |
| `spread(float)` | `spread` | Degrees, 0-360 |
| `angle(float)` | `angle` | 90 is up, 0 is right, increasing anticlockwise |
| `startVelocity(float)` | `startVelocity` | Each particle gets 0.5×-1.5× this |
| `decay(float)` | `decay` | Strictly between 0 and 1 |
| `gravity(float)` | `gravity` | Tripled internally; negative floats upward |
| `drift(float)` | `drift` | Negative left, positive right |
| `ticks(int)` | `ticks` | Frames a particle lives; also drives the fade |
| `scalar(float)` | `scalar` | 1 is the default 10 pixels |
| `flat(bool)` | `flat` | No tumbling, for text particles |
| `zIndex(int)` | `zIndex` | Ignored on a canvas you supplied |
| `option(string, mixed)` | *(any)* | Escape hatch, unvalidated |

## Position

| Method | Notes |
|---|---|
| `origin(float $x, float $y)` | `0,0` top-left, `1,1` bottom-right; off-screen allowed |
| `originX(float)` / `originY(float)` | Move one axis |
| `position(ConfettiPosition\|string)` | A named point |
| `center()` | Origin only; keeps whatever angle is set |
| `top()` `bottom()` `left()` `right()` | Origin and the angle firing inward |
| `topLeft()` `topRight()` `bottomLeft()` `bottomRight()` | Same, from the corners |

Origins are not clamped to the viewport. `y: -0.2` is above the fold, which is
how confetti is made to fall into view.

## Appearance

```php
Confetti::colors('#26ccff', '#a25afd')      // variadic
Confetti::colors(['#26ccff', '#a25afd'])    // or an array
Confetti::palette('gold')                   // or a named palette
Confetti::shapes('circle', 'star')
Confetti::shapeFromPath('M0 10 L5 0 L10 10z', [1, 0, 0, 1, 0, 0])
Confetti::shapeFromText('🎉')
```

Shapes are picked per particle from the list, so repeating one weights it:
`shapes('circle', 'circle', 'star')` gives roughly two circles per star.

`shapeFromPath()`'s matrix is optional but worth supplying for anything that
fires often. Without one, canvas-confetti derives the transform by sampling a
1000×1000 grid in the browser, on the main thread.

`shapeFromText()`'s scalar defaults to null, meaning "inherit the burst's". Keep
it that way unless you have a reason: the glyph is rasterised once at
`10 × scalar` pixels and then scaled by the burst's scalar when drawn, so a
mismatch renders blurred and at the wrong size.

## Timing

| Method | Notes |
|---|---|
| `delay(int $ms)` | Wait before this burst |
| `duration(int $ms)` | How long a continuous effect runs, however the preset was reached |
| `stagger(int $ms)` | Each `then()` advances the delay by this |

## Accessibility

| Method | Notes |
|---|---|
| `reducedMotion(ReducedMotionPolicy\|string)` | `ignore`, `reduce` or `skip`; re-checked before every fire |
| `skipForReducedMotion()` | Shorthand for `skip` |
| `disableForReducedMotion(bool)` | canvas-confetti's own flag, evaluated once at construction |

Reach for `reducedMotion()`. See the [reduced-motion recipe](../recipes/reduced-motion.md)
for why the two are not interchangeable.

## Presets

`preset(ConfettiPreset|string, ...$args)` plus a shorthand each: `stars()`,
`success()`, `magic()`, `rain()`, `realistic()`, `emoji(string $text = '🦄')`,
`fireworks(?int $duration)`, `snow(?int $duration)`,
`schoolPride(?int $duration)`. See [Presets](presets.md).

## Composing and sending

| Method | Notes |
|---|---|
| `then(bool $reset = false)` | Commit a burst; options carry forward unless reset |
| `shoot()` | Send it |
| `via(TransportDriver\|string)` | Force a transport, including a custom one |
| `seed(int)` | Fix the randomness used by `expand()` |
| `expand(bool $expand = true)` | Walk a continuous effect out in PHP |
| `reset()` | Discard everything and start over |

## Inspecting

| Method | Returns |
|---|---|
| `toPayload()` | The `ConfettiPayload` object |
| `toArray()` | The wire payload, deltas only |
| `toResolvedArray()` | With the defaults merged into every burst |
| `toJson(int $flags = 0)` | |
| `resolvedOptions()` | The options a burst would currently use |

`toArray()` looks sparse because anything matching a configured default is
omitted; the browser merges them back. `toResolvedArray()` is the expanded view,
and the one to assert against in a test.

## Mutability

The builder is mutable, which is what makes accumulating in a loop work:

```php
$confetti = Confetti::make();

foreach ($delays as $delay) {
    $confetti->center()->delay($delay)->then();
}

$confetti->shoot();
```

An immutable builder would discard every iteration. The payload objects it
produces *are* immutable, and `__clone` deep-copies the state, so a configured
builder is safe to use as a template:

```php
$base = Confetti::make()->colors('#bb0000', '#ffffff');

(clone $base)->left()->shoot();
(clone $base)->right()->shoot();
```

## Option layers

Options live in three layers (`defaults` from configuration, `preset`, and
`user`) merged in that order at serialisation. A preset can only write to its
own layer, so your calls always win and the order does not matter:

```php
// Identical.
Confetti::spread(90)->fireworks()->shoot();
Confetti::fireworks()->spread(90)->shoot();
```

[Architecture](../architecture.md) explains why this is layered rather than
merged as it goes.

---

[← Docs index](../../README.md#documentation)
