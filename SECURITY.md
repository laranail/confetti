# Security policy

## Reporting a vulnerability

Report security issues privately to **opensource@simtabi.com**. Do not open a
public issue for a suspected vulnerability.

Include the affected version, a description of the issue and, where you can,
a minimal reproduction. You can expect an acknowledgement within three working
days and a substantive reply within ten.

## Supported versions

While the package is pre-1.0, only the latest tagged release receives security
fixes.

## Scope notes

Two areas of this package have a security dimension worth stating explicitly.

**The boot payload is encoded, not escaped by convention.** The
`<script type="application/json" data-confetti-boot>` element is serialised
through `Simtabi\Laranail\Confetti\Support\Json`, which sets
`JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`. Angle brackets
leave as `<` and `>`, so a `</script>` sequence in user-supplied text
(most plausibly via `shapeFromText()`) cannot close the element early and
turn the rest of the payload into markup. `JSON.parse` decodes the escapes, so
nothing is lost in transit. If you are patching that class, keep the flags.

**Asset URLs are built from `config('app.url')`, never the request host.** This
stops a spoofed `Host` or `X-Forwarded-Host` header being reflected into a
`<script src>` attribute. If confetti is served from a different origin,
configure `assets.cdn_url` rather than relying on the request.

The package ships no routes that accept user input beyond a filename matched
against a fixed whitelist in `AssetController`, and writes nothing to disk at
runtime.
