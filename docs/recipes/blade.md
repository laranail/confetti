# Blade

The plain case: an ordinary controller action, a redirect, and confetti on the
page you land on.

Put the runtime in your layout, once:

```blade
    <x-confetti::scripts />
</body>
```

Then fire from anywhere:

```php
public function store(Request $request): RedirectResponse
{
    $order = Order::create($request->validated());

    Confetti::realistic()->shoot();

    return redirect()->route('orders.show', $order);
}
```

The payload is flashed and rendered on the next response. Several `shoot()`
calls in one request arrive as a single effect.

To skip the layout edit entirely, set `CONFETTI_AUTO_INJECT=true` and a
middleware appends the same markup to every HTML response.

See [Transports](../tools/transports.md) and [Assets](../tools/assets.md).

---

[← Docs index](../../README.md#documentation)
