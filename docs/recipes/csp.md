# Content-Security-Policy

The package is designed to work under a strict policy without `unsafe-inline`.

## What lands on the page

```html
<script type="application/json" data-confetti-boot>{…}</script>
<script type="module" src="https://example.test/vendor/confetti/confetti.js?id=…" defer></script>
```

The first is inert data — a script element whose type is not a JavaScript MIME
type is never executed, so CSP does not apply to it and it needs no nonce. The
second is an external module. There is no inline JavaScript anywhere, which a
CI grep enforces rather than assuming.

## The directives you need

```
script-src 'self';
worker-src blob:;
```

`worker-src blob:` is for canvas-confetti's renderer, which it builds from a
blob URL. Without it the library logs a warning and falls back to the main
thread — degraded, not broken. If you would rather not add the directive:

```dotenv
CONFETTI_USE_WORKER=false
```

If the bundle is served from another origin, add that host to `script-src`.

## Nonces

For a policy that uses nonces rather than `'self'`:

```php
'security' => ['csp_nonce' => fn () => request()->attributes->get('csp-nonce')],
```

Or as a literal, if your middleware puts one in config:

```dotenv
CONFETTI_CSP_NONCE=…
```

It is applied to the module tag. The data block still needs none.

## The `</script>` question

The boot block is serialised through `Support\Json` with `JSON_HEX_TAG` and
three companions, so angle brackets leave as `<` and `>`. A literal
`</script>` in user-supplied text — most plausibly via `shapeFromText()` —
cannot close the element early and turn the rest of the payload into markup.
`JSON.parse` decodes the escapes, so nothing is lost.

That is the single most security-relevant line in the package, and an arch test
keeps `json_encode` out of every other file so it cannot be bypassed by
accident.

See [Architecture](../architecture.md) and [SECURITY.md](../../SECURITY.md).

---

[← Docs index](../../README.md#documentation)
