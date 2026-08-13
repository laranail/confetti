# Enums

Eight backed enums built on `laranail/enumerator`. Every setter that takes one
also takes its string value, so they are optional. Reach for them when you want
the IDE to enumerate the choices, or when you are storing a selection.

```php
use Simtabi\Laranail\Confetti\Enums\ConfettiPosition;

Confetti::position(ConfettiPosition::TopLeft)->shoot();
Confetti::position('top-left')->shoot();              // identical
```

## ConfettiShape

`Square`, `Circle`, `Star`: the three canvas-confetti draws natively, and the
list is closed. Anything else renders as a square without complaint, which is
why [validation](validation.md) rejects it.

## ConfettiPosition

The nine named launch points. Each carries its origin and firing angle as
metadata, so the position table is data rather than nine near-identical methods:

```php
ConfettiPosition::BottomLeft->x();      // 0.0
ConfettiPosition::BottomLeft->y();      // 1.0
ConfettiPosition::BottomLeft->angle();  // 60
ConfettiPosition::Center->angle();      // null, keeps whatever angle is set
```

`Center` returns null deliberately, so a centred burst can still be aimed.

## ConfettiPreset

The nine presets, each tagged with what it produces and where it came from:

```php
ConfettiPreset::Snow->kind();        // 'animation'
ConfettiPreset::Snow->isOfficial();  // true, a canvas-confetti recipe
ConfettiPreset::Snow->animation();   // ConfettiAnimation::Snow
ConfettiPreset::Success->kind();     // 'options'
```

`kind()` is `burst`, `animation` or `options`. `isOfficial()` records whether
the effect is a faithful port of an upstream demo recipe or a laranail addition.
The documentation and the demo command both use it rather than claiming
provenance in prose.

## ConfettiAnimation

`Fireworks`, `Snow`, `SchoolPride`: the three that run as a browser loop.

```php
ConfettiAnimation::Snow->defaultDuration();  // 15000
```

## TransportDriver

`Auto`, `Session`, `Livewire`, `Inertia`, `Null`, `Array`. See
[Transports](transports.md).

Note that `via()` takes a plain string as well, which is how a custom driver
registered with `Confetti::extend()` is selected, since it has no case.

## AssetMode

`Route`, `Published`, `Cdn`, `Vite`, `Off`. See [Assets](assets.md).

## ReducedMotionPolicy

`Ignore`, `Reduce`, `Skip`. See the
[reduced-motion recipe](../recipes/reduced-motion.md).

## PresetExpansion

`Client` or `Server`, deciding where a continuous effect is turned into bursts. See
[Animations](animations.md).

## What the toolkit gives you

Every one of these gets the `laranail/enumerator` surface:

```php
ConfettiShape::values();     // ['square', 'circle', 'star']
ConfettiShape::labels();     // ['square' => 'Square', ...]
ConfettiShape::options();    // for a select field
ConfettiShape::coerce($s);   // string or name → case, or null

ConfettiPosition::TopLeft->label();  // 'Top left'
AssetMode::Route->help();            // the one-line explanation
```

Labels are translatable and can be overridden through
`config('enumerator.overrides')` without forking anything.

---

[← Docs index](../../README.md#documentation)
