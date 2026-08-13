# Upgrading

## From `alexsyvolap/filament-confetti`

This package is an independent reimplementation, not a drop-in fork. The fluent
API is deliberately close to the original, but the namespace, the wire format
and two behaviours have changed.

### Namespace and registration

```diff
-use AlexSyvolap\FilamentConfetti\Confetti;
+use Simtabi\Laranail\Confetti\Facades\Confetti;
```

`Confetti` is now a real facade, with a `Confetti` alias registered, so
`\Confetti::realistic()->shoot()` works too.

Filament registration moves:

```diff
-use AlexSyvolap\FilamentConfetti\FilamentConfettiPlugin;
+use Simtabi\Laranail\Confetti\Integrations\Filament\ConfettiPlugin;

 return $panel->plugins([
-    FilamentConfettiPlugin::make(),
+    ConfettiPlugin::make(),
 ]);
```

And outside Filament, which is the point of the rewrite, add the component to
your layout instead:

```blade
<x-confetti::scripts />
```

### Two behaviour changes

**`then()` now carries options forward.** Previously each `then()` cleared
everything, so a palette set before the first burst silently vanished from the
ones after it:

```php
// Before: only the first burst was red.
// Now: both are.
Confetti::colors('#ff0000')->left()->then()->right()->shoot();
```

Pass `then(reset: true)` for the old behaviour. The multi-shot example from the
original README produces identical output either way.

**`stars()` is now the full upstream recipe.** It used to set only a gold
palette, a star shape and a scalar. It now fires the canvas-confetti "Stars"
effect: six bursts across three volleys, with zero gravity and a 50-tick budget.
For the old behaviour:

```php
Confetti::palette('gold')->shapes('star')->scalar(1.2)->shoot();
```

### Corrected effects

`fireworks()` was merging its options in the wrong order, so the package
defaults overwrote them: a 360-degree burst became a 70-degree one, and a
60-tick budget became 200. `realistic()` was missing the upstream `origin: {y:
0.7}`. Both now match the recipes they are named after, which means they *look*
different from before. They look correct.

### The wire format

`snow()`, `schoolPride()` and `fireworks()` no longer expand to hundreds of
pre-computed bursts. They send a compact descriptor and the browser runs the
animation loop, which takes snow from roughly 150KB to roughly 250 bytes and
gives each visitor their own randomness rather than a sequence fixed at render
time.

If you were reading the flashed payload yourself, it has changed shape. Add
`->expand()` to get concrete bursts back, and `->seed()` to make them
reproducible:

```php
Confetti::snow(5000)->seed(1234)->expand()->shoot();
```

### Renamed and removed

| Old | New |
|---|---|
| `Confetti::EVENT` (`fire-confetti`) | `config('laranail.confetti.event')`, default `confetti:fire`. The old name is still listened for. |
| `Alpine.data('filamentConfetti')` | `window.LaranailConfetti`. Alpine is no longer required at all. |
| `FilamentConfettiPlugin::get()` | Removed, as it was unreferenced. Use `filament('laranail-confetti')`. |
| `FilamentAsset::register(...)` | Removed. Delivery is configured through `assets.mode`, which also covers pages outside a panel. |

### Stricter input

Colours must now be hex. canvas-confetti parses a colour by discarding non-hex
characters, so `colors(['red'])` never failed; it painted something arbitrary.
It now throws. Shapes are checked against the three the library actually draws;
anything else used to render silently as a square.

Set `laranail.confetti.validation.strict` to `false` to clamp and log instead of
throwing, if confetti in your application is driven by data you do not control.
