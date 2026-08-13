# Events and hooks

Two ways to reach into an effect: hooks, which change it, and events, which tell
you about it. Both exist on the PHP and browser sides.

## PHP hooks

`before()` runs against every builder the application creates, at the moment it
is made:

```php
use Simtabi\Laranail\Confetti\Builder\ConfettiBuilder;
use Simtabi\Laranail\Confetti\Facades\Confetti;

// In a service provider's boot method.
Confetti::before(fn (ConfettiBuilder $builder) => $builder->palette('brand'));
```

Because hooks run first, anything the caller sets afterwards wins:

```php
Confetti::before(fn ($b) => $b->count(10));

Confetti::count(500)->shoot();   // 500
```

That order is what makes a hook a house style rather than a straitjacket. Set
the defaults you want everywhere, and let individual call sites disagree.

`Confetti::forgetHooks()` clears them, which is mostly useful between tests.

## PHP events

| Event | When | Carries |
|---|---|---|
| `ConfettiPreparing` | A builder was created, before anything is set on it | The mutable builder |
| `ConfettiFired` | A payload reached a transport | The payload and the driver name |
| `ConfettiDiscarded` | A payload had nowhere to go | The payload and a reason |
| `ConfettiRendered` | The markup went into a response | Where from, and whether it carries a payload |

`ConfettiPreparing` does what `before()` does, through the event dispatcher.
Reach for it when the listener belongs somewhere other than a service provider:

```php
Event::listen(ConfettiPreparing::class, function (ConfettiPreparing $event): void {
    if (auth()->user()?->prefersCalm()) {
        $event->builder->reducedMotion('skip');
    }
});
```

`ConfettiDiscarded` fires when confetti was built outside a browser context, so
listen for it if that would mean a bug in your application:

```php
Event::listen(ConfettiDiscarded::class, fn ($e) => Log::warning($e->reason));
```

`ConfettiRendered` answers a question the browser cannot. "Nothing appeared" has
two very different causes: the runtime never reached the page, or it did and no
payload arrived. No `ConfettiRendered` means the first.

## Browser events

Everything the runtime emits is a `CustomEvent` on `window`, so no import is
needed:

```js
window.addEventListener('confetti:burst', (e) => console.log(e.detail.options))
```

| Event | When | Detail |
|---|---|---|
| `confetti:booted` | The runtime read its config and is listening | `event`, `hasPayload` |
| `confetti:burst` | One burst was handed to canvas-confetti | `options`, `reduced` |
| `confetti:animation-start` | A continuous effect started | `animation`, `duration` |
| `confetti:animation-end` | It finished or was aborted | `animation` |
| `confetti:skipped` | Nothing was drawn | `reason`, plus context |
| `confetti:stopped` | Running effects were aborted | `animations` |
| `confetti:error` | Something failed | `error`, `phase` |

`confetti:fire` is deliberately absent from that list. It is the *inbound* event
the server dispatches and the runtime listens to; everything the runtime emits
has a different name so a listener can never re-trigger the effect it is
watching.

`confetti:skipped` carries the reason, and there are three:

| `reason` | Meaning |
|---|---|
| `reduced-motion` | The visitor asked for less motion |
| `already-fired` | This payload id had already been handled |
| `concurrency-cap` | An animation was dropped to stay under the limit |

All three are correct behaviour and all three look identical from the outside,
which is why they say which one happened.

## The subscription helper

`on()` is sugar over `addEventListener` that returns its own unsubscribe
function, so a listener added during a soft navigation can be removed without
keeping the handler in scope:

```js
const stop = LaranailConfetti.on('burst', (e) => track('confetti', e.detail))

// later
stop()
```

It accepts either the short key or the full name, so `on('burst', ...)` and
`on('confetti:burst', ...)` are the same thing.

## Browser hooks

`beforeFire()` transforms the options of every burst, for the things only the
client knows:

```js
// Half the particles on a small screen.
LaranailConfetti.beforeFire((options) => window.innerWidth < 640
  ? { ...options, particleCount: Math.floor(options.particleCount / 2) }
  : options)
```

It runs after the reduced-motion gate and after the defaults are merged, so the
options it receives are the ones canvas-confetti would otherwise have used.

A hook returning nothing leaves the options untouched, which is the shape of a
hook that only wants to look. A hook that throws is reported as a
`confetti:error` with `phase: 'beforeFire'` and the burst fires unchanged.

`beforeFire()` also returns an unsubscribe function.

## Reporting errors

The runtime never swallows a failure. Wiring it into your own reporting is a
few lines:

```js
LaranailConfetti.on('error', (e) => {
  Sentry.captureException(e.detail.error, { extra: e.detail })
})
```

---

[← Docs index](../../README.md#documentation)
