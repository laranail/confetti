# `laranail::confetti.doctor`

Reports the setup that decides whether confetti actually appears.

```bash
php artisan laranail::confetti.doctor
```

Every check here corresponds to a way the package can be configured into a state
where nothing shows up and nothing errors, which is the failure mode worth
having a command for.

## What it checks

**Browser bundle.** That it exists, with its size and content hash. Fails if
missing, which normally means an incomplete checkout.

**Asset delivery.** That the resolved mode can deliver anything at all:
`published` with nothing in `public/vendor/confetti`, `cdn` with no URL
configured, a `vite` entry missing from the manifest, or `off`, which is
legitimate but means the application must load the runtime itself.

**Canvas.** Warns when `runtime.canvas` is set alongside a non-default
`zIndex`, because canvas-confetti ignores `zIndex` on a canvas it did not
create. The runtime applies the stacking itself, so the warning is a nudge to
check the element is not clipped by an ancestor.

**Web worker.** Whether it is on, and the Content-Security-Policy directive it
needs. The worker is built from a blob URL, so a strict policy without
`worker-src blob:` makes canvas-confetti log and fall back to the main thread.

**Preset expansion.** Warns when set to `server`, since that ships hundreds of
kilobytes and gives every visitor identical randomness.

**Detected stacks.** Which of Livewire, Filament and Inertia are installed, so
you can tell at a glance whether the transport you expected is available.

## Exit codes

`0` when everything passes or only warns; `1` when a check fails, so it works
in a deploy pipeline as a gate.

## Example

```
  laranail/confetti

  ✓ Browser bundle
    Built: confetti.js, 21.5 KB (hash a3f9c21e4d70).

  ✓ Asset delivery
    Served from a content-hashed route. No publish step, and a stale bundle is
    impossible.

  ✓ Canvas
    Using the library's own canvas.

  ✓ Web worker
    Enabled. The worker is built from a blob URL, so a strict
    Content-Security-Policy needs `worker-src blob:`. Without it canvas-confetti
    logs a warning and falls back to the main thread.

  ✓ Preset expansion
    Continuous effects ship as descriptors and run in the browser.

  ✓ Detected stacks
    Livewire, Filament.
```

The checks are also registered with `laranail/package-tools`, so
`laranail::package-tools.doctor` picks them up alongside every other laranail
package's.

---

[← Docs index](../../README.md#documentation)
