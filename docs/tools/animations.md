# Animations

The three continuous effects (`fireworks`, `snow` and `schoolPride`) run as a
loop in the browser rather than as a list of pre-computed bursts.

## Why

These are `requestAnimationFrame` loops in the upstream recipes. Expanding one
server-side means serialising every iteration: snow at its default duration is
roughly five hundred `confetti()` calls, about 150KB of JSON on the response,
with every random value already decided, so every visitor watches the same
snowfall, flake for flake.

As a descriptor it is about 250 bytes and each browser rolls its own:

```json
{
  "animation": "snow",
  "duration": 15000,
  "options": { "particleCount": 1, "startVelocity": 0, "colors": ["#ffffff"] },
  "params": { "ticksMin": 200, "ticksMax": 500, "skewFrom": 1, "skewTo": 0.8,
              "gravity": [0.4, 0.6], "scalar": [0.4, 1], "drift": [-0.4, 0.4] }
}
```

`options` are canvas-confetti options applied to every particle the loop emits.
`params` are the loop's own knobs, with `[min, max]` pairs wherever a value is
randomised per frame. Keeping them separate is what lets the runtime stay a
faithful port of the recipe while remaining configurable from PHP.

## The loop driver

All three run on one shared driver, and it is built on `requestAnimationFrame`
even for the interval-based fireworks recipe. Browsers throttle timers in a
background tab and `setInterval` compensates by firing the backlog when the tab
is focused again: coming back to a page and being met with sixty simultaneous
fireworks. An rAF clock with an accumulator simply does not advance while the
tab is hidden.

The elapsed clock excludes hidden time, so a fifteen-second effect is fifteen
seconds of *visible* effect. Turn that off with
`runtime.pause_when_hidden = false`.

`runtime.max_concurrent_animations` (default 3) caps how many run at once; the
oldest is aborted past that, so a page firing a fifteen-second effect on every
action does not stack them until the tab gives up.

## Stopping one

```php
Confetti::stop();
```

Sends an abort instruction. Worth having, because fifteen seconds is a long time
to be stuck with if the user has moved on.

The adapters do this for you on a soft navigation. See the
[SPA navigation recipe](../recipes/spa-navigation.md).

## Expanding in PHP

`->expand()` walks the loop in PHP and ships the bursts it produces:

```php
Confetti::snow(5000)->seed(1234)->expand()->shoot();
```

Two reasons to want this: a test that asserts on concrete bursts, and an
application that does not load this package's runtime at all.

`seed()` fixes the random sequence, so the output is byte-for-byte reproducible.
Without one, expansion uses `presets.seed` from configuration, or a fixed
default. The generator is a small xorshift carried by the package rather than
PHP's global one, so expanding confetti never disturbs randomness elsewhere in
the request.

Expansion refuses past `limits.max_bursts` (default 200):

```
Expanding this effect produced 500 bursts, over the configured limit of 200.
```

That ceiling is deliberate; without it, `expand()` on a default-duration
snowfall quietly produces a payload in the hundreds of kilobytes.

Set `presets.expansion` to `server` to make it the default everywhere. The
doctor command warns when you have.

## Registering your own

From JavaScript:

```js
LaranailConfetti.registerAnimation('spiral', (descriptor, context) => {
  // Return a promise; abort on context.signal.
})
```

And emit a matching descriptor from a custom preset:

```php
$draft->addAnimation(Animation::make(
    animation: ConfettiAnimation::from('spiral'),
    duration: 8000,
    params: ['turns' => 3],
));
```

An unknown animation name is reported to the console and dispatched as a
`confetti:error` event rather than failing silently.

---

[← Docs index](../../README.md#documentation)
