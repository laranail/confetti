# Presets

Nine ready-made effects. Six are faithful ports of the canvas-confetti demo
recipes (the particle counts, timings and physics are copied from upstream, and
a test asserts each number) and three are this package's own.

| Preset | Kind | Upstream recipe |
|---|---|---|
| `realistic()` | 5 bursts | yes |
| `stars()` | 6 bursts | yes |
| `emoji()` | 9 bursts | yes |
| `fireworks()` | animation | yes |
| `snow()` | animation | yes |
| `schoolPride()` | animation | yes |
| `success()` | options only | no |
| `magic()` | options only | no |
| `rain()` | options only | no |

"Options only" means the preset changes settings and nothing else, so it
combines with a position or another effect. "Bursts" means it produces several
`confetti()` calls. "Animation" means it runs as a loop in the browser. See
[Animations](animations.md).

## realistic()

Five overlapping bursts, all fired at once, at different spreads and velocities:
a tight fast core, a medium spread, a wide light cloud, and two slow outliers.
Firing them in sequence would give five small pops instead of one explosion.

The origin sits at `y: 0.7` rather than mid-screen, which is what makes it look
like it came from something on the page.

Particle counts are ratios of 200, floored: 50, 40, 70, 20, 20.

```php
Confetti::realistic()->shoot();
```

## stars()

Gold stars bursting outward and hanging in the air: `spread: 360`, `ticks: 50`,
`gravity: 0`, `decay: 0.94`, `startVelocity: 30`. Each volley pairs 40 stars at
scalar 1.2 with 10 small circles at 0.75, fired three times 100ms apart.

Zero gravity is what makes it work: the particles stop where they land and fade
over the tick budget rather than falling.

> This is a whole effect, not a colour scheme. If you only want the gold
> palette: `Confetti::palette('gold')->shapes('star')->scalar(1.2)->shoot()`.

## emoji()

Emoji particles hanging and fading rather than falling: `gravity: 0`,
`decay: 0.96`, `ticks: 60`, `scalar: 2`. Each volley is three bursts: thirty
tumbling glyphs, five flat ones facing the viewer, and fifteen half-size circles
that stop it looking like clip-art, fired three times 100ms apart.

```php
Confetti::emoji('🎉')->shoot();
```

The glyph inherits the burst's scalar automatically, which keeps the rasterised
bitmap and the drawn size in agreement.

## fireworks()

A pair of 360-degree bursts every 250ms for the duration, one on each side, with
the particle count falling in step with the time remaining so the display tapers
rather than stopping dead. Launch heights run from `-0.2` to `0.8`, above the
fold at one end, because particles fall.

```php
Confetti::fireworks()->shoot();      // 15 seconds
Confetti::fireworks(5000)->shoot();  // or say how long
```

## snow()

One flake per animation frame, launched with no velocity so gravity alone
carries it, each with its own weight, size and drift. The `skew` term creeps
from 1 to 0.8 over the duration, narrowing the band flakes are born in, so the
snowfall settles in rather than switching on. The tick budget shrinks alongside
the remaining time, so the last flakes fade instead of being cut off.

```php
Confetti::snow()->shoot();
```

## schoolPride()

Two jets firing inward from the left and right edges at 60 and 120 degrees. Two
particles per frame from each side, which at sixty frames a second is a steady
stream rather than a burst.

```php
Confetti::colors('#bb0000', '#ffffff')->schoolPride()->shoot();
```

## success(), magic(), rain()

The three additions. `success()` sets a green palette. `magic()` sets small
purple and cyan circles at scalar 0.8. `rain()` fires wide and slow from the top
with halved gravity and a long tick budget.

Because they only set options, they compose:

```php
Confetti::success()->topLeft()->count(200)->shoot();
```

## Combining with your own options

Presets write to their own option layer, so anything you set wins regardless of
order:

```php
// Identical.
Confetti::colors('#123456')->schoolPride()->shoot();
Confetti::schoolPride()->colors('#123456')->shoot();
```

## Registering your own

```php
use Simtabi\Laranail\Confetti\Builder\OptionStack;
use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Payload\PayloadDraft;

final class BrandPreset implements Preset
{
    public function name(): string
    {
        return 'brand';
    }

    public function apply(OptionStack $stack, PayloadDraft $draft): void
    {
        $stack->setPresetMany([
            'colors' => ['#0d9488', '#f59e0b'],
            'scalar' => 1.1,
        ]);

        $draft->addPresetBurst(['particleCount' => 80, 'spread' => 60]);
        $draft->addPresetBurst(['particleCount' => 40, 'spread' => 120], 100);
    }
}
```

Register it in a service provider and use it by name:

```php
Confetti::registerPreset('brand', fn (): Preset => new BrandPreset);

Confetti::preset('brand')->shoot();
```

Write to `setPreset`, never `setUser`. The `user` layer belongs to the caller,
and that separation is what keeps preset and caller order-independent.

See the [custom-preset recipe](../recipes/custom-preset.md) for a worked example.

## Inspecting the cost

```bash
php artisan laranail::confetti.demo               # list them
php artisan laranail::confetti.demo snow          # ~250 bytes
php artisan laranail::confetti.demo snow --expand # what that saves you
```

---

[← Docs index](../../README.md#documentation)
