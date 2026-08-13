# Assets

Five ways to get the browser runtime onto a page, set with
`laranail.confetti.assets.mode`.

The package ships two bundles, both with canvas-confetti built in, so there is
no npm step for consumers:

| File | Format | Used by |
|---|---|---|
| `confetti.iife.js` | IIFE, exposes `window.LaranailConfetti` | `route`, `published`, `cdn` |
| `confetti.esm.mjs` | ES module | applications importing it themselves |

## route — the default

Served by the package from a URL carrying the bundle's content hash:

```
GET /vendor/confetti/confetti.js?id=a3f9c21e4d70
Cache-Control: public, max-age=31536000, immutable
ETag: "a3f9c21e4d70"
```

No publish step, and a stale bundle is impossible — upgrading the package
changes the hash, which changes the URL. Cached forever, revalidated with the
ETag by clients that ignore that.

The filename is matched against a fixed map of the two bundles rather than
joined onto a path, so there is no traversal surface. A missing bundle answers
404 with an explanatory JavaScript comment rather than a 500 — confetti should
never be the reason a page fails.

Set `assets.route` to move it, `assets.middleware` to put it behind something.

## published

```bash
php artisan vendor:publish --tag=laranail::confetti-assets
```

Copies the bundles to `public/vendor/confetti/`. No route is registered.

Appropriate when `public/` is fronted by a CDN. Remember to re-publish on
upgrade — nothing detects a stale copy, which is the trade for not having a
route.

## cdn

```php
'assets' => [
    'mode' => 'cdn',
    'cdn_url' => 'https://cdn.example.com/confetti.iife.js',
    'cdn_integrity' => 'sha384-…',
],
```

Emits a `<script>` with the integrity hash and `crossorigin="anonymous"` when
one is set. With no `cdn_url` configured, nothing is emitted and a warning is
logged — the doctor command reports it as a failure.

## vite

Resolves the bundle from your own Vite manifest:

```js
// vite.config.js
input: ['resources/js/app.js', 'resources/js/confetti.js']
```

```js
// resources/js/confetti.js
import { start } from '@laranail/confetti/source'

start()
```

A missing manifest entry throws in Laravel, so it is caught and downgraded to
the route mode with a logged warning — losing a build optimisation beats a 500
on every page. The doctor command verifies the entry at deploy time.

## off

Emits no script tag. For applications that load the runtime themselves:

```js
import { start } from '@laranail/confetti'

start()
```

The boot payload block is still emitted, so the runtime finds its configuration
and any flashed effect.

## Where the URL comes from

Always `config('app.url')`, never the request host. A `Host` or
`X-Forwarded-Host` header is attacker-controlled on many deployments, and
reflecting one into a `<script src>` would let a spoofed header decide where the
browser fetches executable code from.

If confetti must come from another origin, use `cdn_url` rather than relying on
the request.

## Cache busting

`assets.version` overrides the cache-busting string. Left null, the bundle's
own xxh128 content hash is used — which is what makes the immutable cache header
safe.

## What lands on the page

```html
<script type="application/json" data-confetti-boot>{…}</script>
<script type="module" src="…" defer></script>
```

The first is inert data — a script element whose type is not a JavaScript MIME
type is never executed — carrying the defaults and any flashed payload. The
second is the runtime. No inline JavaScript in either, which is what lets the
package work under a strict Content-Security-Policy with no `unsafe-inline`.

## Checking it

```bash
php artisan laranail::confetti.doctor
```

Reports the bundle's size and hash, the resolved mode, and whether that mode can
actually deliver anything.

---

[← Docs index](../../README.md#documentation)
