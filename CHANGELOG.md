# Changelog

All notable changes to `laranail/confetti` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Named effects. Declare your own confetti configurations in
  `laranail.confetti.effects` and fire them by name with
  `Confetti::effect('checkout')`, so what an effect looks like is a config
  decision rather than a code change. Registrable at runtime too, with
  `Confetti::registerEffect()`.
- `Confetti::before()` hooks, applied to every builder the application creates.
  They run first, so an individual call site still overrides them.
- `ConfettiPreparing` and `ConfettiRendered` events, joining `ConfettiFired` and
  `ConfettiDiscarded`. The first carries the mutable builder so policy can be
  applied application-wide; the second says the runtime reached a page, which
  is what separates "nothing was sent" from "nothing could have run".
- A browser event vocabulary: `confetti:booted`, `confetti:burst`,
  `confetti:animation-start`, `confetti:animation-end`, `confetti:skipped`,
  `confetti:stopped` and `confetti:error`, each a `CustomEvent` on `window`.
  `confetti:skipped` names which of the three silent outcomes occurred.
- `LaranailConfetti.on()` and `.off()`, where `on()` returns its own
  unsubscribe function, and `LaranailConfetti.beforeFire()` for transforming
  bursts from the browser.

### Fixed

- `duration()` set a builder property that nothing read, so
  `snow()->duration(5000)` silently ran for the default fifteen seconds. It now
  applies however the preset was reached.
- `integrations.livewire.enabled` was documented but never read, so turning the
  Livewire transport off did nothing.
- `runtime.debug` was shipped to the browser and ignored there. It now logs the
  three reasons an effect can draw nothing without raising: the reduced-motion
  gate, a payload already fired, and an animation evicted at the concurrency cap.

### Added

- `Confetti::reset()`, which stops running effects and clears the canvas. The
  runtime already handled the action; nothing could produce one.

### Removed

- `integrations.filament.use_filament_assets`, which was documented but never
  implemented. The asset-delivery modes already cover panels.

## [0.1.0] - 2026-08-13

Initial release. A vanilla Laravel 13 confetti package built on the laranail
platform, with Filament, Livewire and Inertia as optional adapters.

### Added

- Fluent `Confetti` builder with a three-layer option stack (`defaults` →
  `preset` → `user`), resolved at serialisation so preset and user calls are
  order-independent.
- Nine presets ported faithfully to the official canvas-confetti recipes:
  `stars`, `success`, `magic`, `rain`, `realistic`, `emoji`, `fireworks`,
  `snow`, `schoolPride`.
- Client-side animation engine: `snow`, `schoolPride` and `fireworks` ship as
  ~220-byte declarative descriptors executed by a `requestAnimationFrame` loop
  in the browser, instead of hundreds of server-expanded shots.
- `->expand()` escape hatch materialising an animation into concrete bursts
  server-side, deterministic under `->seed()`.
- Pluggable transports (`session`, `livewire`, `inertia`, `null` and `array`)
  behind an auto-detecting `Manager` seam.
- Five asset-delivery modes: `route` (default), `published`, `cdn`, `vite` and
  `off`.
- `<x-confetti::scripts />` Blade component and an optional `InjectConfetti`
  middleware that splices the tag before `</body>`.
- Filament panel plugin, a Livewire `InteractsWithConfetti` trait and Inertia
  page-prop sharing. All are optional, and none is a production dependency.
- `Confetti::fake()` with `assertFired`, `assertFiredTimes`,
  `assertNothingFired` and `assertAnimation`.
- Artisan commands `laranail::confetti.install`, `laranail::confetti.demo` and
  `laranail::confetti.doctor`.
- Typed enums (`ConfettiShape`, `ConfettiPosition`, `ConfettiPreset`,
  `ConfettiAnimation`, ...) built on `laranail/enumerator`.
- Validation of every option at the setter, with a `ConfettiException` family.

[Unreleased]: https://github.com/laranail/confetti/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/laranail/confetti/releases/tag/v0.1.0
