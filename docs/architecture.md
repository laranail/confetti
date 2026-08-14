# Architecture

How the package is put together, and why each seam is where it is.

## The shape of it

```
Confetti::snow()->shoot()
   │
   ├─ OptionStack        defaults < preset < user, merged at the end
   ├─ PayloadDraft       bursts and animation descriptors
   ▼
ConfettiPayload          { v, id, action, bursts[], animations[] }
   │
   ▼
TransportManager         session | livewire | inertia | null | array
   │
   ▼
<x-laranail-confetti::scripts />  a JSON data block + an external module
   │
   ▼
resources/dist/confetti.iife.js
```

## Why options are layered

Options arrive from three places (the package defaults, a preset, and the
caller) and the only sensible precedence is that order. The obvious
implementation merges them as they arrive; this one keeps them in three separate
layers and merges at serialisation.

That buys three things.

**Order independence.** A preset can only write to the `preset` layer and a
caller only to `user`, so `->spread(90)->fireworks()` and
`->fireworks()->spread(90)` produce the same burst. With eager merging they do
not.

**No accidental clobbering.** The bug this replaced was one `array_merge()` with
its arguments the wrong way round, in the fireworks preset. The generic defaults
overwrote the values the preset existed to set: a 360-degree burst became 70
degrees, and a 60-tick budget became 200. The effect ran three times too long
and looked nothing like a firework, and nothing failed. With layers there is no
argument order to get wrong.

**A small wire format.** The layer stack knows which options differ from the
defaults, so a burst can carry only those. The defaults travel once, in the boot
payload, and the browser applies the same precedence: canvas-confetti's own
defaults, then ours, then the burst's.

## Why continuous effects run in the browser

Snow, school pride and fireworks are `requestAnimationFrame` loops in the
upstream recipes. Expanding one server-side means serialising every iteration:
snow at its default duration is roughly five hundred `confetti()` calls, about
150KB of JSON on the response, with every random value already decided, so
every visitor watches an identical snowfall.

Instead the payload carries a descriptor:

```json
{ "animation": "snow", "duration": 15000, "options": {...}, "params": {...} }
```

About 250 bytes, and each browser rolls its own randomness. `options` are
canvas-confetti options applied to every particle; `params` are the loop's own
knobs, with `[min, max]` pairs wherever a value is randomised per frame. Keeping
them apart is what lets the runtime stay a faithful port while remaining
configurable from PHP.

`->expand()` still walks the loop in PHP when you want concrete bursts: for a
snapshot test, or for an application that does not load this runtime. It draws
from a seeded generator so the output is reproducible, and refuses past
`limits.max_bursts` rather than quietly emitting a 150KB payload.

## Why the transport is a seam

The same effect has to reach the browser differently depending on the request:
Livewire dispatches a browser event, Inertia shares a page prop, an ordinary
request flashes to the session. Automatic detection walks those in order of
specificity and stops at the first that can run.

It never throws. An unresolvable context (a console command, a queued job)
falls through to a transport that discards the payload and dispatches an event.
Confetti is decorative; it should not be able to break a worker. A driver named
explicitly through `via()` does throw, because asking for something specific and
silently getting something else is worse than an error.

### The flash-replay bug

Worth describing, because the obvious implementation has it.

To let several `shoot()` calls in one request accumulate, you want to merge with
what is already flashed:

```php
session()->flash($key, array_merge(session()->get($key, []), $new));
```

On a flashed key, `get()` returns what the *previous* request flashed: the
payload currently being rendered. Re-flashing it extends its life by another
request, so the same confetti fires again on the next page, and the one after
that, for as long as anything keeps firing. It reads as "confetti is stuck on",
and it is maddening to trace because the code that fired it ran once.

The fix is that `SessionTransport` never reads the session. Accumulation happens
in a request-scoped object that lives and dies with the request. That binding is
`scoped()` rather than `singleton()` on purpose: under Octane a singleton
outlives the request and would leak one visitor's confetti to the next, which is
the same bug wearing a different hat.

## Why the optional integrations are duck-typed

Filament, Livewire and Inertia are development dependencies. Every reference to
them in the shipped code is a string class name checked with `class_exists()`
and `is_callable()`, never an import. Two reasons: an application that does not
use Livewire should not be made to install it, and `isLivewireRequest()` and
`current()` are manager internals that have moved between major versions. If
either disappears, the transport reports itself unavailable and the session
transport takes over, so confetti arrives a navigation later instead of not at
all.

An arch test enforces this by walking PHP's own tokens rather than the text, so
a *string* class name passes and a compiled reference does not.

### Filament needs two guards

`ConfettiPlugin` names `Filament\Contracts\Plugin` in its `implements` clause,
which PHP resolves when it compiles the class. Merely autoloading the file
without Filament installed is a fatal error, and a `class_exists()` check inside
a method cannot help, because the failure happens before any method runs. So the file
returns early, before the class declaration, leaving the class undefined. That
is exactly what `class_exists()` reports, so callers can check for it normally.

A conditionally-defined class cannot be listed in `extra.laravel.providers`,
though, because Laravel would try to instantiate something that may not exist.
So the opt-in "apply to every panel" mode lives in a separate, ordinary provider
that gates itself. The two guards solve different problems and neither
substitutes for the other.

## Why the boot payload is a JSON block

The runtime needs the server's payload and defaults. An earlier design put them
in an Alpine `x-data` attribute, which made Alpine a hard requirement and put an
executable expression on the page.

Instead the server writes:

```html
<script type="application/json" data-confetti-boot>{...}</script>
```

A script element whose type is not a JavaScript MIME type is never executed, so
this is inert data. No `unsafe-inline` in the Content-Security-Policy, no
framework needed to carry it, and the runtime can be a plain external module,
which is what makes the package work identically on Blade, Livewire, Inertia and
Filament.

It does need encoding. A literal `</script>` inside the block closes the element
early and turns the rest into markup, and text supplied by a user and passed to
`shapeFromText()` is the realistic route to that happening. `Support\Json` sets
`JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`, so angle brackets
leave as `<` and `>` and `JSON.parse` decodes them back. That is the
single most security-relevant line in the package, and an arch test keeps
`json_encode` out of every other file so it cannot be bypassed by accident.

## Why asset URLs ignore the request

The asset URL is always built from `config('app.url')`. A `Host` or
`X-Forwarded-Host` header is attacker-controlled on many deployments, and
reflecting one into a `<script src>` attribute would let a spoofed header decide
where the browser fetches executable code from.

The default delivery mode serves the bundle from a route whose URL carries the
bundle's content hash, so it can be cached forever and can never be stale after
an upgrade. Four other modes exist for applications that would rather publish,
use a CDN, build it themselves, or load nothing.

## Why validation runs in the setter

Range and format checks happen where the value is set, so the stack trace points
at the line that supplied it. Cross-field rules (a text shape's scalar against
its burst's, the burst ceiling on an expanded animation) run at serialisation,
because they need the final layer resolution to exist.

Strict mode is the default. Clamping and logging is available, and appropriate
when confetti is driven by data you do not control, but defaulting to it would
mean staging and production differ silently, which is worse than an exception a
test would have caught.

## <a name="naming"></a>Why every public name carries the vendor

Everything this package registers is prefixed, and none of it is called just
`confetti`:

| Surface | Name | Used as |
|---|---|---|
| Blade component | `laranail-confetti` | `<x-laranail-confetti::scripts />` |
| View namespace | `laranail-confetti` | `view('laranail-confetti::components.scripts')` |
| Route middleware | `laranail-confetti` | `->middleware('laranail-confetti')` |
| Artisan commands | `laranail::confetti.` | `php artisan laranail::confetti.doctor` |
| Alpine component | `laranailConfetti` | `<div x-data="laranailConfetti">` |

Laravel keeps view namespaces, component prefixes and middleware aliases in flat
maps keyed by that name. Two packages claiming the same key do not collide
loudly; the second silently replaces the first, and it surfaces much later as a
missing view or the wrong middleware running. "Confetti" is a generic enough
word that another package, or the application itself, could reasonably want it.

The separators differ because each registry parses its key differently, and each
choice is forced rather than stylistic:

- **Commands use `::`** because Symfony resolves an exact command name before it
  splits on `:`, so `laranail::confetti.doctor` dispatches even though the empty
  segment in `::` would fail its own name validator.
- **Middleware cannot use `::`**, because Laravel splits an alias on `:` to take
  parameters, the way `throttle:60,1` does. `laranail::confetti` would resolve as
  the middleware `laranail` with the parameter `:confetti`.
- **Blade components cannot use `::`** either, since `::` is already what
  separates the prefix from the component inside the tag.
- **The Alpine name is camelCase**, because `x-data` is evaluated as a JavaScript
  expression: `x-data="laranail-confetti"` is a subtraction of two undefined
  names.

The commands carried short aliases (`confetti:doctor`) for a while. They are
gone: an alias that reintroduces the generic name gives back exactly what the
convention is for.

---

[← Docs index](../README.md#documentation)
