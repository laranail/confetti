# SPA navigation

A fifteen-second snowfall started before a `wire:navigate` has no reason to
stop, and would keep falling over whatever the visitor navigated to.

The bundled runtime handles this. On `livewire:navigating` and `inertia:before`
it aborts every running animation; on `livewire:navigated` it re-reads the boot
block, because the new page brings its own payload.

Nothing to configure — the adapters attach when those frameworks are present and
are inert when they are not.

## Stopping one yourself

```php
Confetti::stop();
```

From the browser:

```js
LaranailConfetti.stop()    // abort running animations
LaranailConfetti.reset()   // stop and clear the canvas
```

## If you import the runtime yourself

`start()` wires the adapters up for you:

```js
import { start } from '@laranail/confetti'

const runtime = start()
```

Or attach them individually:

```js
import { createRuntime, registerLivewireAdapter } from '@laranail/confetti'

const runtime = createRuntime()

runtime.listen()
registerLivewireAdapter(runtime)
runtime.fireBootPayload()
```

## Firing twice

A page restored from the back/forward cache re-runs its boot script. Every
payload carries a ULID and the runtime remembers what it has already fired, so
that restore does not replay the effect.

See [Animations](../tools/animations.md).

---

[← Docs index](../../README.md#documentation)
