# Validation

Every option is checked as it is set, so a mistake surfaces at the line that
made it rather than rendering as something subtly wrong.

That matters more here than in most packages, because canvas-confetti almost
never errors. Bad input produces a different effect, not an exception.

## Colours must be hex

canvas-confetti parses a colour by deleting every character that is not a hex
digit and reading what is left, expanding it as shorthand if fewer than six
survive. So:

- `'red'` → `'ed'` → expanded → some arbitrary colour
- `'rgb(255, 0, 0)'` → `'2550'` → likewise

Neither fails. Both paint the wrong thing. So they are rejected here:

```php
Confetti::colors('red');
// InvalidColor: Confetti colours must be hex strings such as '#26ccff' or
// '#fff', got 'red'. canvas-confetti strips non-hex characters rather than
// failing, so a CSS colour name or an rgb() value would render as an arbitrary
// colour instead of raising an error.
```

Accepted with or without the `#`, in three or six digits, and normalised to
lowercase `#rrggbb` so payloads dedupe and test comparisons are not defeated by
casing.

## Shapes are a closed set

canvas-confetti draws exactly three natively: `square`, `circle` and `star`. Its
draw routine branches on the latter two and falls through to a square for
everything else, so an unrecognised name renders silently.

```php
Confetti::shapes('triangle');
// InvalidShape: Unknown confetti shape 'triangle'. canvas-confetti draws only
// 'square', 'circle', 'star' natively; any other name renders as a square
// without warning. For anything else use shapeFromPath() or shapeFromText().
```

## Numeric ranges

| Option | Rule | Why |
|---|---|---|
| `decay` | strictly 0 < d < 1 | A per-frame velocity multiplier. At 1 or more particles never slow down and the burst runs out its whole tick budget at speed. |
| `particleCount` | 0-`limits.max_particles` | |
| `ticks` | 1-`limits.max_ticks` | |
| `spread` | 0-360 | |
| `scalar` | 0 < s ≤ 10 | |
| `angle` | any finite; normalised into one turn | |
| `gravity`, `drift` | any finite, including negative | Negative gravity floats particles upward, which several recipes rely on |
| `origin` | any finite; **not** clamped | |

### Origins are deliberately unbounded

Off-screen origins are load-bearing. The upstream fireworks and snow recipes
both launch from `y ≈ -0.2`, above the fold, because particles fall. Clamping
to 0..1 would put every firework in the bottom half of the screen and quietly
break both effects.

## Path matrices

A path shape's transform is six finite numbers in DOMMatrix order, as a plain
array:

```php
Confetti::shapeFromPath('M0 10 L5 0 L10 10z', [1, 0, 0, 1, 0, 0]);
```

The DefinitelyTyped stubs describe this as a `DOMMatrix` instance, which is
wrong at runtime: the drawing code guards on `Array.isArray`, so an object is
ignored and the shape draws untransformed.

Passing `null` lets canvas-confetti derive one, which it does by sampling a
1000×1000 grid with `isPointInPath`, in the browser, on the main thread. Fine
once; worth computing and hard-coding for anything that fires often.

## Strict mode

`validation.strict` defaults to `true`, so invalid input throws.

With it off, values are clamped to the nearest legal one and logged once per key
per request. That is appropriate when confetti is driven by data you do not
control and a decorative effect must never break a page.

It is not the default because an effect that silently differs between staging
and production is worse than an exception a test would have caught.

## Catching failures

Every exception implements `ConfettiException` while extending the SPL type that
describes it, so you can catch the whole family in one clause:

```php
use Simtabi\Laranail\Confetti\Exceptions\ConfettiException;

try {
    Confetti::colors($fromUser)->shoot();
} catch (ConfettiException) {
    // Decorative; never worth breaking the request over.
}
```

| Exception | Extends | Raised by |
|---|---|---|
| `InvalidColor` | `InvalidArgumentException` | non-hex colours, empty or unknown palettes |
| `InvalidShape` | `InvalidArgumentException` | unknown shapes, bad matrices, empty paths |
| `InvalidOption` | `InvalidArgumentException` | out-of-range numbers, exceeded limits |
| `InvalidPreset` | `InvalidArgumentException` | an unregistered preset |
| `TransportUnavailable` | `RuntimeException` | a named transport that cannot run |
| `AssetNotBuilt` | `RuntimeException` | a missing bundle, from the doctor command |

## In the browser

The runtime validates nothing; the server already did. What it does do is
surface failures instead of swallowing them: a shape it cannot build is reported
to the console and dispatched as a `confetti:error` event, and the burst falls
back to the default shapes rather than failing outright.

```js
window.addEventListener('confetti:error', (event) => {
  Sentry.captureException(event.detail.error, { extra: event.detail })
})
```

---

[← Docs index](../../README.md#documentation)
