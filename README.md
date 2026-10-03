# laranail/confetti

[![Tests](https://img.shields.io/github/actions/workflow/status/laranail/confetti/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/laranail/confetti/actions/workflows/tests.yml)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/laranail/confetti/static-analysis.yml?branch=main&label=static%20analysis&style=flat-square)](https://github.com/laranail/confetti/actions/workflows/static-analysis.yml)
[![License MIT](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

`laranail/confetti` is not published to Packagist, so there is no registry-version badge to show: see [Install](#install).

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

## Quick start guide and usage

### Getting started

1. Put the runtime on your pages, once. Use the `<x-laranail-confetti::scripts />` component shown in Install, or let the middleware append it to every HTML response:

   ```dotenv
   CONFETTI_AUTO_INJECT=true
   ```

2. Check the setup. It reports the bundle, the asset delivery mode and any Content-Security-Policy directives you need:

   ```bash
   php artisan laranail::confetti.doctor
   ```

3. Optionally, `php artisan laranail::confetti.install` writes `config/laranail/confetti.php`; every setting has a working default.

### Usage

```php
use Simtabi\Laranail\Confetti\Facades\Confetti;

public function store(StoreOrderRequest $request)
{
    $order = $this->placeOrder->handle($request->toData());

    Confetti::realistic()->shoot();

    return redirect()->route('orders.show', $order);
}
```

Several bursts at once, with shared options said once:

```php
Confetti::colors('#bb0000', '#ffffff')
    ->left()->count(100)->then()
    ->right()->count(100)
    ->shoot();
```

The full walkthrough is in [Getting started](docs/getting-started.md); everything else is in the [documentation index](#documentation).

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
