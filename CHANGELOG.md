# Changelog

All notable changes to `laranail/confetti` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-08-13

First release. A vanilla Laravel 13 confetti package built on the laranail
platform, where Filament, Livewire and Inertia are optional adapters rather than
the foundation. None of the three is a production dependency, and the test
matrix runs the whole suite with all of them uninstalled.

### The builder

- Fluent API over `canvas-confetti`, with a typed setter for each of its fifteen
  options and validation at the point of use, so a bad value is reported at the
  line that supplied it.
- Options resolve through three ordered layers, package defaults then preset
  then caller, merged at serialisation. Preset and caller calls are therefore
  order-independent.
- Nine presets. Six are faithful ports of the canvas-confetti demo recipes, down
  to particle counts and timings, and a test asserts each number.
- Nine named positions, each pairing a viewport origin with the angle that fires
  away from the nearest edge.

### Effects, hooks and events

- Named effects: declare confetti configurations in `laranail.confetti.effects`
  and fire them by name with `Confetti::effect('checkout')`, so what an effect
  looks like is a config decision. Registrable at runtime with
  `Confetti::registerEffect()`.
- `Confetti::before()` hooks, applied to every builder. They run first, so an
  individual call site still overrides them.
- Events: `ConfettiPreparing`, `ConfettiFired`, `ConfettiDiscarded` and
  `ConfettiRendered`. The first carries the mutable builder for
  application-wide policy; the last says the runtime reached a page, which is
  what separates a payload that was never sent from a runtime that never
  arrived.
- A browser event vocabulary, each a `CustomEvent` on `window`:
  `confetti:booted`, `confetti:burst`, `confetti:animation-start`,
  `confetti:animation-end`, `confetti:skipped`, `confetti:stopped` and
  `confetti:error`. `confetti:skipped` names which of the three silent outcomes
  occurred, since reduced motion, a duplicate payload and the concurrency cap
  are all correct behaviour and all look like a broken install.
- `LaranailConfetti.on()` and `.off()`, where `on()` returns its own
  unsubscribe function, plus `beforeFire()` for transforming bursts from the
  browser.

### Delivery

- Continuous effects ship as declarative descriptors, roughly 250 bytes, and run
  as a `requestAnimationFrame` loop in the browser. Expanded server-side, snow
  at its default duration is about 150KB with the randomness already decided, so
  every visitor watches the same snowfall. `->expand()` still does that when
  concrete bursts are wanted, deterministically under `->seed()`.
- Transports for session, Livewire, Inertia and null, behind an auto-detecting
  `Manager` seam that never throws. Firing confetti from a queued job is inert
  rather than fatal.
- Five asset-delivery modes: `route` (the default, content-hashed and immutably
  cached), `published`, `cdn`, `vite` and `off`.
- `<x-confetti::scripts />`, an optional auto-inject middleware, and a Filament
  panel plugin, all rendering the same markup so a panel and a plain page cannot
  drift apart.
- The boot payload is an inert JSON block rather than an inline script, so the
  package works under a strict Content-Security-Policy with no `unsafe-inline`
  and needs no JavaScript framework.

### Safety

- Effect definitions reach only the builder methods that configure an effect.
  They are checked against an allowlist rather than `method_exists()`, so a
  definition cannot call `shoot()`, `via()`, `expand()`, `seed()`, `then()` or
  `reset()`. An effect describes what confetti looks like; deciding when it
  fires and which transport carries it stays at the call site, where it is
  visible. The distinction costs nothing while every definition is written by a
  developer in a config file, and `registerEffect()` exists so that they need
  not be.
- The boot payload is encoded with the four `JSON_HEX_*` flags, so a `</script>`
  in text handed to `shapeFromText` renders inert rather than closing the block.

### Tooling

- `Confetti::fake()` with `assertFired`, `assertFiredTimes`,
  `assertNothingFired`, `assertAnimation` and `assertBurstCount`.
- Artisan commands `laranail::confetti.install`, `.demo` and `.doctor`, plus an
  `about` section.
- Typed enums built on `laranail/enumerator`, and a `ConfettiException` family
  that keeps each concrete exception on the SPL type describing it.
- PHPStan runs at level 8 with no baseline, CodeQL scans the browser runtime,
  and a CI check verifies that every SHA-pinned action matches the version in
  its comment.

[Unreleased]: https://github.com/laranail/confetti/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/laranail/confetti/releases/tag/v0.1.0
