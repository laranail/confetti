# Transports

Five drivers carrying a payload from PHP to the browser, behind one
auto-detecting seam.

| Driver | Mechanism | When it applies |
|---|---|---|
| `livewire` | Dispatches a browser event from the current component | A Livewire request with a component handling it |
| `inertia` | Shares the payload as a page prop | An Inertia visit, integration enabled |
| `session` | Flashes it for the next response | An ordinary request with a session |
| `null` | Discards it | Console, queue, no session |
| `array` | Records it in memory | `Confetti::fake()` |

## Automatic resolution

`auto` — the default — walks the drivers in order of specificity and stops at
the first available one:

1. a driver forced with `via()`
2. Livewire, if this is a Livewire request with a current component
3. Inertia, if this is an Inertia visit and `integrations.inertia` is on
4. the session, if one has been started
5. otherwise, discard

**It never throws.** An unresolvable context falls through to the null
transport, which discards the payload and dispatches
`ConfettiDiscarded`. Confetti is decorative; firing it from a queued job is a
mistake, but not one worth crashing a worker over.

A driver named explicitly *does* throw when it cannot run:

```php
Confetti::realistic()->via('livewire')->shoot();
// TransportUnavailable: The 'livewire' confetti transport is unavailable:
// this is not a Livewire request, or no component is handling it.
```

Asking for something specific and silently getting something else is worse than
an error.

## The session transport

The one that makes `redirect()->back()` followed by confetti work.

It **never reads the session** — only writes. That is not an oversight, it is
the fix for a bug worth knowing about. The obvious way to let several `shoot()`
calls accumulate is to merge with what is already flashed:

```php
session()->flash($key, array_merge(session()->get($key, []), $new));
```

On a flashed key, `get()` returns what the *previous* request flashed — the
payload currently being rendered. Re-flashing it extends its life by another
request, so the same confetti fires again on the next page, and the one after
that, indefinitely. It reads as "confetti is stuck on" and is hard to trace,
because the code that fired it ran once.

Accumulation happens in a request-scoped object instead. Several `shoot()` calls
in one request still arrive as one effect:

```php
Confetti::count(10)->shoot();
Confetti::count(20)->shoot();
// One payload, two bursts.
```

## The Livewire transport

Dispatches a browser event from the component handling the request. Livewire
forwards those to `window`, which is where the runtime listens — so confetti
fires on a component action with no page load and no Alpine involved.

Everything is duck-typed through string class names, so `livewire/livewire`
stays a development dependency and the transport degrades to the session one if
Livewire's internals move.

## The Inertia transport

Shares the payload as a page prop. An Inertia visit returns JSON, so there is no
`</body>` to inject into and no full page load to carry a flash. The client
adapter fires it on `inertia:success`.

Off by default — sharing a prop only helps if that adapter is loaded. See the
[Inertia recipe](../recipes/inertia.md).

## Custom transports

```php
use Simtabi\Laranail\Confetti\Contracts\Transport;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;

final class BroadcastTransport implements Transport
{
    public function name(): string
    {
        return 'broadcast';
    }

    public function available(): bool
    {
        return true;
    }

    public function send(ConfettiPayload $payload): void
    {
        broadcast(new ConfettiEvent($payload->toArray()));
    }
}
```

Register it in a service provider:

```php
Confetti::extend('broadcast', fn (): Transport => new BroadcastTransport);

Confetti::realistic()->via('broadcast')->shoot();
```

Firing confetti on other people's screens is the obvious case the built-in
drivers do not cover.

`available()` is consulted during automatic resolution, so it must be cheap and
must not throw. A transport that needs a package which may not be installed
should check with `class_exists()` rather than importing the class.

## Events

| Event | When |
|---|---|
| `ConfettiFired` | After a payload reaches a transport; carries the driver name |
| `ConfettiDiscarded` | When a payload had nowhere to go; carries a reason |

Listen for the second if confetti fired outside a request would indicate a bug
in your application.

---

[← Docs index](../../README.md#documentation)
