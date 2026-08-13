# Filament

Register the plugin on a panel:

```php
use Simtabi\Laranail\Confetti\Integrations\Filament\ConfettiPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugins([
        ConfettiPlugin::make(),
    ]);
}
```

Then fire from anywhere in the panel:

```php
Action::make('publish')
    ->action(function (): void {
        $this->record->publish();

        Confetti::stars()->shoot();
    });
```

The plugin renders the same markup a plain Laravel page gets, so a panel and the
rest of your site share one runtime, one bundle and one asset setting. A test
asserts that parity byte for byte.

## Options

```php
ConfettiPlugin::make()
    ->renderHook(PanelsRenderHook::HEAD_END)   // default: BODY_END
    ->enabled(fn (): bool => auth()->user()->likesConfetti);
```

## Every panel, without editing a PanelProvider

```php
'integrations' => ['filament' => ['auto' => true]],
```

Off by default — a package silently modifying every panel in an application is
the kind of helpfulness that becomes hard to trace.

## Filament is not a dependency

`filament/filament` is a development dependency here. The plugin class is
defined only when Filament is installed, so the package autoloads cleanly
without it. See [Architecture](../architecture.md) for why that needs two
separate guards.

---

[← Docs index](../../README.md#documentation)
