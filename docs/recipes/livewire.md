# Livewire

Confetti fires immediately on a component action, with no page load. The
transport detects the Livewire request on its own.

```php
use Simtabi\Laranail\Confetti\Facades\Confetti;

class Checkout extends Component
{
    public function complete(): void
    {
        $this->order->complete();

        Confetti::realistic()->shoot();
    }
}
```

There is an optional trait if you prefer the intent visible at the call site:

```php
use Simtabi\Laranail\Confetti\Integrations\Livewire\InteractsWithConfetti;

class Checkout extends Component
{
    use InteractsWithConfetti;

    public function complete(): void
    {
        $this->confetti()->realistic()->shoot();
    }
}
```

Livewire dispatches the event on `window`, which is where the runtime listens,
with no Alpine involved. `livewire/livewire` is not a dependency of this package; the
transport is duck-typed and degrades to a session flash if Livewire's internals
move.

For `wire:navigate`, see [SPA navigation](spa-navigation.md).

See [Transports](../tools/transports.md).

---

[← Docs index](../../README.md#documentation)
