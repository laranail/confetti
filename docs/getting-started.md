# Getting started

Firing confetti, composing effects, and knowing which method to reach for.

## The shortest version

```php
use Simtabi\Laranail\Confetti\Facades\Confetti;

Confetti::realistic()->shoot();
```

`shoot()` sends the effect. Everything before it describes what to send.

Confetti fired during a request that ends in a redirect arrives on the page you
redirect to; fired from a Livewire component it arrives immediately, with no
page load. You do not have to choose. See [Transports](tools/transports.md).

## Presets

Nine ready-made effects. Six are faithful ports of the canvas-confetti demo
recipes; three are this package's own.

```php
Confetti::realistic()->shoot();     // one substantial explosion
Confetti::stars()->shoot();         // gold stars, hanging and fading
Confetti::emoji('🎉')->shoot();     // emoji particles
Confetti::fireworks()->shoot();     // 15 seconds of fireworks
Confetti::snow()->shoot();          // 15 seconds of snowfall
Confetti::schoolPride()->shoot();   // two jets firing inward
Confetti::success()->shoot();       // a green palette
Confetti::magic()->shoot();         // small purple and cyan circles
Confetti::rain()->shoot();          // a wide, slow fall
```

The three continuous ones take a duration in milliseconds:

```php
Confetti::snow(5000)->shoot();
```

See [Presets](tools/presets.md) for what each one does and where it came from.

## Positions

Nine named launch points, each firing away from the nearest edge:

```php
Confetti::topLeft()->shoot();
Confetti::bottom()->count(200)->shoot();
Confetti::origin(0.25, 0.75)->shoot();   // or an explicit point
```

## Options

Every canvas-confetti option, with a typed setter:

```php
Confetti::make()
    ->count(200)
    ->spread(120)
    ->startVelocity(45)
    ->decay(0.92)
    ->gravity(0.8)
    ->colors('#26ccff', '#a25afd', '#ff5e7e')
    ->shapes('circle', 'star')
    ->scalar(1.2)
    ->shoot();
```

Values are checked as you set them, so a mistake is reported at the line that
made it rather than rendering as something subtly wrong. See
[Validation](tools/validation.md).

## Several bursts at once

`then()` commits the current options as one burst and starts another. Options
carry forward, so shared settings only need saying once:

```php
Confetti::colors('#bb0000', '#ffffff')
    ->left()->count(100)->then()
    ->right()->count(100)
    ->shoot();
```

To space them out, either set a delay per burst or let `stagger()` do it:

```php
Confetti::make()->stagger(150)
    ->left()->then()
    ->center()->then()
    ->right()
    ->shoot();
```

## Combining a preset with your own options

Presets and your own calls live in separate layers, so order does not matter and
yours always wins:

```php
// These two are identical.
Confetti::colors('#bb0000', '#ffffff')->schoolPride()->shoot();
Confetti::schoolPride()->colors('#bb0000', '#ffffff')->shoot();
```

## Seeing what you built

Nothing is sent until `shoot()`, so you can inspect it first:

```php
Confetti::realistic()->toArray();          // the wire payload
Confetti::realistic()->toResolvedArray();  // with the defaults merged in
Confetti::realistic()->toJson();
```

`toArray()` carries only what differs from the configured defaults, which is why
it looks sparse. `toResolvedArray()` is the expanded view.

## Testing it

```php
Confetti::fake();

$this->post('/orders');

Confetti::assertFired();
Confetti::assertAnimation('fireworks');
```

See [Testing](tools/testing.md).

## Where next

- [Configuration](configuration.md): every setting and what it changes
- [The builder](tools/builder.md): the complete API
- [Architecture](architecture.md): why the pieces are shaped the way they are
- Recipes for [Blade](recipes/blade.md), [Livewire](recipes/livewire.md),
  [Inertia](recipes/inertia.md) and [Filament](recipes/filament.md)

---

[← Docs index](../README.md#documentation)
