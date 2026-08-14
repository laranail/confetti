# laranail/confetti

[![Packagist Version](https://img.shields.io/packagist/v/laranail/confetti.svg?style=flat-square)](https://packagist.org/packages/laranail/confetti)
[![Tests](https://img.shields.io/github/actions/workflow/status/laranail/confetti/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/laranail/confetti/actions/workflows/tests.yml)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/laranail/confetti/static-analysis.yml?branch=main&label=static%20analysis&style=flat-square)](https://github.com/laranail/confetti/actions/workflows/static-analysis.yml)
[![License MIT](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

> A fluent confetti builder for Laravel: canvas-confetti wrapped in a typed, validated PHP API, with Blade, Livewire, Inertia and Filament adapters.

PHP `^8.4.1` on Laravel `^13`.

## Install

```bash
composer require laranail/confetti
```

The service provider is auto-discovered. Add the runtime to your layout, once,
before `</body>`:

```blade
<x-laranail-confetti::scripts />
```

Then fire it from anywhere:

```php
use Simtabi\Laranail\Confetti\Facades\Confetti;

Confetti::realistic()->shoot();
```

## <a name="documentation"></a>Documentation

Full documentation is at
**[opensource.simtabi.com/documentation/laranail/confetti](https://opensource.simtabi.com/documentation/laranail/confetti/)**
covering installation and the three ways to get the runtime onto a page, the builder
and every canvas-confetti option, the nine presets, named effects, hooks and
events, the client-side animation engine, asset delivery, transports,
validation, testing, the Artisan commands,
and recipes for Blade, Livewire, Inertia, Filament, custom shapes, custom
presets, Content-Security-Policy and reduced motion.

## Community

Questions and ideas belong in
[Discussions](https://github.com/laranail/confetti/discussions); reproducible
faults in [Issues](https://github.com/laranail/confetti/issues).

## Contributing & security

See [CONTRIBUTING.md](CONTRIBUTING.md) and [SECURITY.md](SECURITY.md). Report
vulnerabilities privately to `opensource@simtabi.com`.

## Credits

Particle rendering is [canvas-confetti](https://github.com/catdad/canvas-confetti)
by Kiril Vatev (ISC), bundled with the package.

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
