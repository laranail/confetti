# `laranail::confetti.install`

Publishes the configuration and prints what is left to do.

```bash
php artisan laranail::confetti.install
php artisan laranail::confetti.install --force   # overwrite an existing config
```

The package works without running this — it auto-discovers, and the default
asset mode needs no publish step. The command exists to put the config file
somewhere you can edit it, and to state plainly which of the three ways of
getting the runtime onto a page you still have to pick.

## What it does

Publishes `config/laranail/confetti.php`.

If `assets.mode` is `published`, it also publishes the browser bundle to
`public/vendor/confetti/` — in every other mode that would be dead weight.

Then it prints the remaining step, which is the one thing it cannot do for you:

```
  One step left — get the runtime onto your pages.

  Place the component in your layout, before </body>:
      <x-confetti::scripts />

  Or let the middleware do it for every HTML response:
      CONFETTI_AUTO_INJECT=true

  On a Filament panel, register the plugin instead:
      ->plugins([ConfettiPlugin::make()])

  Then fire it:
      Confetti::realistic()->shoot();

  Verify with php artisan laranail::confetti.doctor.
```

## Publish tags

The individual tags, if you would rather call `vendor:publish` yourself:

| Tag | Publishes |
|---|---|
| `laranail::confetti-config` | `config/laranail/confetti.php` |
| `laranail::confetti-assets` | `resources/dist` → `public/vendor/confetti` |
| `laranail::confetti-views` | the Blade component, for overriding |

---

[← Docs index](../../README.md#documentation)
