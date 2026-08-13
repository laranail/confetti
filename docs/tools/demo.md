# `laranail::confetti.demo`

Prints the payload a preset produces, and what it costs on the wire.

```bash
php artisan laranail::confetti.demo                                  # list the presets
php artisan laranail::confetti.demo realistic                        # inspect one
php artisan laranail::confetti.demo snow --expand --duration=3000    # what expansion costs
php artisan laranail::confetti.demo snow --json                      # the raw payload
```

Mostly a way to see the cost of an effect before shipping it, and the contrast
is the point:

```
  snow

    Bursts      0
    Animations  1
    Wire size   264 B

    Runs as an animation loop in the browser. Add --expand to see the
    bursts it would take to do the same thing from PHP.
```

```
  snow --expand --duration=3000

    Bursts      100
    Animations  0
    Wire size   19 KB
```

Expanding a full-length continuous effect is refused rather than printed:
fifteen seconds of snow is five hundred bursts, past the `limits.max_bursts`
ceiling. The command says so and suggests a duration, which is the useful shape
of that answer:

```
  Expanding this effect produced 500 bursts, over the configured limit of 200.
  Shorten the duration, raise laranail.confetti.limits.max_bursts, or drop
  expand() and let the browser run the animation loop instead, which is both
  the default and far smaller on the wire.

  Try a shorter duration, for example:
      laranail::confetti.demo snow --expand --duration=3000
```

The listing marks which presets are faithful ports of canvas-confetti demo
recipes and which are this package's own:

```
  Presets

    stars          burst (canvas-confetti recipe)
    success        options
    magic          options
    rain           options
    realistic      burst (canvas-confetti recipe)
    emoji          burst (canvas-confetti recipe)
    fireworks      animation (canvas-confetti recipe)
    snow           animation (canvas-confetti recipe)
    schoolPride    animation (canvas-confetti recipe)
```

Custom presets registered with `Confetti::registerPreset()` appear here too.

---

[← Docs index](../../README.md#documentation)
