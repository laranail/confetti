# Named effects

Your own confetti configurations, declared in config and fired by name.

```php
Confetti::effect('checkout')->shoot();
```

## Why not just call the builder

Because the call site should say what it means, not what it looks like:

```php
Confetti::count(180)->spread(100)->palette('brand')->scalar(1.1)->shoot();
Confetti::effect('checkout')->shoot();
```

The second survives someone deciding checkout should be quieter. That decision
becomes a config edit rather than a code change, and it lands everywhere the
effect is used at once.

## Declaring them

```php
// config/laranail/confetti.php
'effects' => [

    'celebrate' => [
        'preset' => 'realistic',
    ],

    'subtle' => [
        'count' => 40,
        'spread' => 45,
        'position' => 'top',
        'ticks' => 120,
    ],

    'award' => [
        'preset' => 'stars',
        'palette' => 'gold',
    ],

    'party' => [
        'preset' => 'schoolPride',
        'duration' => 6000,
    ],

],
```

Each key is a **builder method** and each value its arguments. Every method that
configures how confetti looks is available: the particle options, the positions,
colours and shapes, timing, the reduced-motion policy, and the presets. See
[the builder](builder.md) for the full surface.

What an effect may *not* do is decide when confetti fires or which transport
carries it, so `shoot()`, `via()`, `expand()`, `seed()`, `then()` and `reset()`
are rejected. See [Errors](#errors) for what that looks like.

A list is spread as separate arguments, which is how a two-argument method
works:

```php
'corner' => ['origin' => [0.9, 0.1]],   // origin(0.9, 0.1)
```

An associative array, or a plain value, is passed as one argument:

```php
'brand' => ['colors' => ['#0d9488', '#f59e0b']],
```

## It is still a builder

`effect()` returns the builder with the definition applied, so a call site can
keep going:

```php
Confetti::effect('subtle')->topLeft()->shoot();
Confetti::effect('party')->duration(2000)->shoot();
```

Anything set afterwards wins, the same way it does over a preset.

## Effects, presets and palettes

Three things that sound similar and are not.

| | What it is | Where it lives |
|---|---|---|
| **Palette** | A named set of colours | Config |
| **Preset** | A whole effect written in PHP | The package, or your own class |
| **Effect** | A named combination of builder calls | Config |

A palette is an ingredient. A preset is a recipe the package ships, six of them
faithful ports of the canvas-confetti demos. An effect is your application
saying "this is what *we* mean by celebrating", usually by combining the other
two.

## Errors

An unknown name lists what is configured:

```
Unknown confetti effect 'checkut'. Configured effects: 'celebrate', 'subtle',
'award', 'party'. Add one under laranail.confetti.effects, or register it at
runtime with Confetti::registerEffect().
```

An option that is not a builder method is rejected rather than ignored, since
silently dropping it is how a config file ends up describing behaviour the
package does not have:

```
Confetti effect 'subtle' sets 'particleCount', which is not a builder method.
Each key in an effect names a method on the builder, so use the method name:
'count' rather than 'particleCount', 'palette' rather than 'colours'.
```

A method that exists but does not belong in an effect gets its own message,
because it is a different mistake:

```
Confetti effect 'party' sets 'shoot', which is not something an effect may do.
An effect describes what confetti looks like; deciding when it fires, which
transport carries it, or how it expands belongs at the call site. Call 'shoot()'
on the builder instead: Confetti::effect('party')->shoot(...).
```

The dispatch methods are checked against an allowlist rather than
`method_exists()`, so an effect cannot reach control flow. That distinction
costs nothing while every definition is written by a developer in a config file.
It matters as soon as one is not, and `registerEffect()` exists precisely so
definitions can come from elsewhere.

## Applying something to everything

An effect is per call site. For a rule that applies to every effect in the
application, use a hook instead:

```php
Confetti::before(fn ($builder) => $builder->palette('brand'));
```

See [Events and hooks](events.md).

---

[← Docs index](../../README.md#documentation)
