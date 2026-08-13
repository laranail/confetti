# A custom preset

Wrap your own effect so it reads like the built-in ones.

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
        // Shared settings go on the preset layer, so a caller can override any
        // of them, in either order.
        $stack->setPresetMany([
            'colors' => ['#0d9488', '#f59e0b', '#ffffff'],
            'scalar' => 1.1,
            'ticks'  => 150,
        ]);

        // Each burst carries only what makes it different from its siblings.
        $draft->addPresetBurst(['particleCount' => 80, 'spread' => 60, 'startVelocity' => 50]);
        $draft->addPresetBurst(['particleCount' => 40, 'spread' => 120, 'decay' => 0.91], 120);
    }
}
```

Register it in a service provider:

```php
Confetti::registerPreset('brand', fn (): Preset => new BrandPreset);
```

And use it:

```php
Confetti::preset('brand')->shoot();
Confetti::preset('brand')->topLeft()->shoot();   // your options still win
```

It appears in `laranail::confetti.demo` alongside the built-ins.

## Taking arguments

The factory receives whatever `preset()` is given:

```php
Confetti::registerPreset('brand', fn (int $count = 80): Preset => new BrandPreset($count));

Confetti::preset('brand', 200)->shoot();
```

## Two rules

**Write to `setPreset`, never `setUser`.** The `user` layer belongs to the
caller, and that separation is what makes preset and caller order-independent.

**Use `addPresetBurst`, not `addBurst`.** A preset burst holds only its own
overrides; the shared settings are merged in at serialisation, which is what
lets options set *after* the preset still reach it.

See [Presets](../tools/presets.md).

---

[← Docs index](../../README.md#documentation)
