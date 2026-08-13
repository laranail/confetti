# Installation

Install the package, put the runtime on your pages, and fire.

## Requirements

PHP `^8.4.1` or `^8.5`, on Laravel `^13`. Nothing else is required. Filament,
Livewire and Inertia are optional adapters, and the browser bundle ships with
the package, so there is no npm step.

## Install

```bash
composer require laranail/confetti
```

The service provider is auto-discovered, and a `Confetti` facade alias is
registered.

## Get the runtime onto your pages

Confetti needs a small JavaScript runtime on the page that will show it. There
are three ways to put it there, and you only need one.

### The Blade component

The explicit option. Place it once in your layout, before `</body>`:

```blade
    <x-confetti::scripts />
</body>
```

### The middleware

If you would rather not touch a layout, the package can append the same markup
to every HTML response:

```dotenv
CONFETTI_AUTO_INJECT=true
```

It skips redirects, JSON, Inertia and Livewire responses, and anything matching
`inject.except`. It also skips a page that already has the component, so the two
can coexist while you migrate.

This is off by default because silently rewriting responses should be a decision
rather than a surprise.

### The Filament plugin

For a panel, register the plugin instead:

```php
use Simtabi\Laranail\Confetti\Integrations\Filament\ConfettiPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugins([
        ConfettiPlugin::make(),
    ]);
}
```

All three render the same markup, so a panel and the rest of your site share one
runtime and one asset setting.

## Check it

```bash
php artisan laranail::confetti.doctor
```

It reports the bundle, the resolved asset delivery mode, which stacks it can
see, and the Content-Security-Policy directives you will need if you have one.

## Publish the configuration

Optional; every setting has a working default.

```bash
php artisan laranail::confetti.install
```

That writes `config/laranail/confetti.php` and prints the remaining steps. See
[Configuration](configuration.md) for what is in it.

## Fire something

```php
use Simtabi\Laranail\Confetti\Facades\Confetti;

Confetti::realistic()->shoot();
```

If nothing happens, the doctor command is the fastest way to find out why.

---

[← Docs index](../README.md#documentation)
