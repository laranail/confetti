# Reduced motion

A visitor who has asked for less motion should not be met with fifteen seconds
of snowfall.

## The default

`runtime.reduced_motion` is `reduce`: animations collapse to a single burst at
half the particle count and a short tick budget, and a multi-burst preset fires
only its first burst. The acknowledgement survives; the motion does not.

The other two:

```dotenv
CONFETTI_REDUCED_MOTION=skip     # draw nothing
CONFETTI_REDUCED_MOTION=ignore   # fire everything
```

`ignore` is only appropriate when the confetti *is* the page.

## Per effect

```php
Confetti::snow()->skipForReducedMotion()->shoot();
Confetti::stars()->reducedMotion('ignore')->shoot();
```

## Why not canvas-confetti's own option

canvas-confetti has `disableForReducedMotion`, and it works — but it evaluates
the media query once, when the library builds its cannon, and caches the answer.
Setting it on a later burst does nothing, and a visitor who changes the setting
mid-session is not picked up until a reload.

The package forwards the option for completeness and runs its own gate, checked
before every fire, which is also what makes the middle `reduce` option possible.

```php
Confetti::realistic()->disableForReducedMotion()->shoot();   // forwarded, but see above
Confetti::realistic()->reducedMotion('skip')->shoot();       // reach for this
```

---

[← Docs index](../../README.md#documentation)
