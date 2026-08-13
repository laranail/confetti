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

Each key is a **builder method** and each value its arguments, so anything the
builder can do an effect can declare. See [the builder](builder.md) for the
full surface.

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

## Applying something to everything

An effect is per call site. For a rule that applies to every effect in the
application, use a hook instead:

```php
Confetti::before(fn ($builder) => $builder->palette('brand'));
```

See [Events and hooks](events.md).

---

[← Docs index](../../README.md#documentation)
