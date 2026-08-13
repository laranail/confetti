# Configuration

Every setting in `config/laranail/confetti.php`, and what changes when you touch it.

Publish it with `php artisan laranail::confetti.install`. Every value has a
working default, so publishing is optional.

## Master switch

| Key | Default | Env |
|---|---|---|
| `enabled` | `true` | `CONFETTI_ENABLED` |

When false, `shoot()` becomes a no-op and the component emits nothing. Effects
still build and can be inspected with `toArray()`, so tests keep working.

## Events

| Key | Default |
|---|---|
| `event` | `confetti:fire` (`CONFETTI_EVENT`) |
| `legacy_event` | `fire-confetti` |

`legacy_event` exists so a payload flashed before an upgrade still fires after
it. Set it to `null` once you are past that window.

## Transport

| Key | Default | Notes |
|---|---|---|
| `transport.driver` | `auto` (`CONFETTI_TRANSPORT`) | `auto`, `session`, `livewire`, `inertia`, `null`, `array` |
| `transport.session_key` | `laranail.confetti` | The flash key |
| `transport.inertia_prop` | `confetti` | The shared page prop |

`auto` resolves per request: Livewire, then Inertia, then a session flash, then
discard. See [Transports](tools/transports.md).

## Default options

`defaults` holds canvas-confetti options applied to every burst. They travel to
the browser once, in the boot payload; individual bursts carry only what
differs.

| Key | Default |
|---|---|
| `particleCount` | `150` (`CONFETTI_PARTICLE_COUNT`) |
| `spread` | `70` |
| `ticks` | `200` |
| `shapes` | `['square', 'circle']` |
| `colors` | canvas-confetti's seven-colour palette |
| `zIndex` | `100` |
| `disableForReducedMotion` | `false` |

`gravity` is tripled inside canvas-confetti, so 1 is a brisk fall rather than a
gentle one.

## Palettes

Named colour sets, reached with `palette('gold')`. Ships with `default`,
`success`, `magic`, `gold`, `snow` and `pride`. A `null` palette means "use the
default colours".

Colours must be hex. See [Validation](tools/validation.md) for why that is
enforced rather than assumed.

## Presets

| Key | Default | Notes |
|---|---|---|
| `presets.expansion` | `client` (`CONFETTI_PRESET_EXPANSION`) | `client` or `server` |
| `presets.duration` | `15000` (`CONFETTI_PRESET_DURATION`) | Default for continuous effects |
| `presets.seed` | `null` (`CONFETTI_PRESET_SEED`) | Fixes server expansion |

`client` sends a compact descriptor and the browser runs the loop. `server`
walks it in PHP and ships every burst: hundreds of kilobytes, and identical
randomness for every visitor. Worth it only to assert on bursts in a test, or if
the application does not load this runtime. See [Animations](tools/animations.md).

## Asset delivery

| Key | Default |
|---|---|
| `assets.mode` | `route` (`CONFETTI_ASSETS`) |
| `assets.route` | `/vendor/confetti` |
| `assets.middleware` | `[]` |
| `assets.cdn_url` | `null` |
| `assets.cdn_integrity` | `null` |
| `assets.vite_entry` | `resources/js/confetti.js` |
| `assets.version` | `null`, meaning the bundle's content hash |
| `assets.defer` | `true` |

Five modes: `route`, `published`, `cdn`, `vite`, `off`. See
[Assets](tools/assets.md) for what each emits and when to pick it.

## Automatic injection

| Key | Default |
|---|---|
| `inject.auto` | `false` (`CONFETTI_AUTO_INJECT`) |
| `inject.group` | `web` (`CONFETTI_INJECT_GROUP`) |
| `inject.only` | `[]` |
| `inject.except` | `telescope*`, `horizon*`, `_debugbar*`, `livewire/*` |

With `auto` on, a middleware appends the tags before the closing `</body>` of
every HTML response. Off by default because silently rewriting responses should
be a decision rather than a surprise.

## Security

| Key | Default |
|---|---|
| `security.csp_nonce` | `null` (`CONFETTI_CSP_NONCE`) |

A nonce for the script tag under a strict Content-Security-Policy. The boot
payload needs none; it is a JSON data block, which the browser never executes.
See the [CSP recipe](recipes/csp.md).

## Browser runtime

| Key | Default | Notes |
|---|---|---|
| `runtime.use_worker` | `true` (`CONFETTI_USE_WORKER`) | Needs CSP `worker-src blob:` |
| `runtime.canvas` | `null` (`CONFETTI_CANVAS`) | A CSS selector for your own canvas |
| `runtime.reduced_motion` | `reduce` (`CONFETTI_REDUCED_MOTION`) | `ignore`, `reduce`, `skip` |
| `runtime.pause_when_hidden` | `true` | Animations pause in a background tab |
| `runtime.max_concurrent_animations` | `3` | The oldest is aborted past this |
| `runtime.shape_cache_size` | `32` | Rasterised text shapes held in memory |
| `runtime.debug` | `false` (`CONFETTI_DEBUG`) | Logs why an effect drew nothing |

Two of these have sharp edges worth knowing.

**`use_worker`** builds a worker from a blob URL. Under a strict CSP without
`worker-src blob:`, canvas-confetti logs a warning and falls back to the main
thread on its own: degraded, not broken.

**`debug`** explains the quiet outcomes. Confetti can fire, draw nothing and
raise no error for three legitimate reasons: the reduced-motion gate suppressed
it, the payload was one the runtime had already fired, or an animation was
evicted to stay under the concurrency cap. From the outside all three look like
a broken install, so with this on each is logged to the console.

**`canvas`** points at your own element. canvas-confetti ignores `zIndex` on a
canvas it did not create and applies no positioning to it, so the runtime styles
the element itself. The doctor command warns when both are set.

## Limits

Ceilings on anything that could be threaded through from user input.
canvas-confetti has none of its own; it will accept a million particles and
then spend the frame budget on them.

| Key | Default |
|---|---|
| `limits.max_particles` | `1000` |
| `limits.max_ticks` | `2000` |
| `limits.max_delay` | `60000` |
| `limits.max_duration` | `60000` |
| `limits.max_bursts` | `200` |

## Validation

| Key | Default | Env |
|---|---|---|
| `validation.strict` | `true` | `CONFETTI_STRICT` |

Strict raises on out-of-range input. With it off, values are clamped and logged
This is appropriate when confetti is driven by data you do not control, but it does
mean staging and production can differ silently.

## Integrations

| Key | Default |
|---|---|
| `integrations.filament.enabled` | `true` (`CONFETTI_FILAMENT`) |
| `integrations.filament.auto` | `false` |
| `integrations.filament.hook` | `null`, meaning `panels::body.end` |
| `integrations.livewire.enabled` | `true` |
| `integrations.inertia.enabled` | `false` (`CONFETTI_INERTIA`) |

Each is inert unless its package is installed, so leaving them enabled costs
nothing.

`filament.auto` applies the plugin to every panel without touching a
`PanelProvider`. Inertia is off by default because sharing a page prop only
helps if the client adapter is loaded.

---

[← Docs index](../README.md#documentation)
