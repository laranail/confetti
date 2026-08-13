# Contributing

Thanks for considering a contribution.

## Getting set up

```bash
composer install
npm install
```

The package depends on three sibling laranail packages resolved through git,
not Packagist, so `composer install` needs network access to GitHub.

## Running the checks

```bash
composer test          # Pest
composer lint          # Pint, PHPStan and Rector, in that order
npm test               # Vitest, for the browser runtime
npm run build          # rebuild resources/dist
```

CI runs the PHP suite twice — once with `filament/filament`,
`livewire/livewire` and `inertiajs/inertia-laravel` installed, and once with all
three removed. The second leg is what proves the package works on a plain
Laravel app, so if you add an integration, guard its tests with
`->skip(fn () => ! class_exists(...))` rather than assuming the dependency.

## The built bundle is committed

`resources/dist/` holds the browser bundle and **is** tracked in git, because
consumers install through Composer and never run npm. If you change anything
under `resources/js/`, run `npm run build` and commit the result — CI fails on
drift between source and bundle.

## Conventions

- PHP `^8.4.1`, `declare(strict_types=1)` everywhere, Pint's Laravel preset.
- PHPStan runs at level 8 with no baseline. A baseline is permission to be
  wrong; if analysis fails, fix the code or narrow the ignore to one path with a
  comment explaining why.
- Artisan commands are named `laranail::confetti.<command>`.
- Every option this package exposes must correspond to something
  `canvas-confetti` actually reads. When in doubt, check its source rather than
  its README — several documented details there are incomplete, and a few
  DefinitelyTyped signatures are wrong.
- Arch tests enforce the module boundaries (no `Filament\` outside
  `src/Integrations/Filament/`, no `session()` outside `SessionTransport`, no
  `mt_rand()` outside `Support\Seed`). If one fails, the boundary is the thing
  to reconsider before the test.

## Pull requests

Branch from `main`, keep the subject line under 72 characters and in the
imperative mood, and explain *why* in the body. Add a `CHANGELOG.md` entry under
`## [Unreleased]` for anything user-facing.

## Security

Do not open a public issue for a vulnerability — see [SECURITY.md](SECURITY.md).
