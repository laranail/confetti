# Inertia

An Inertia visit returns JSON, so there is no `</body>` to inject into and no
full page load to carry a session flash. The payload rides along as a page prop.

Enable it:

```dotenv
CONFETTI_INERTIA=true
```

Then fire as usual; the transport detects the visit:

```php
public function store(Request $request): RedirectResponse
{
    Order::create($request->validated());

    Confetti::fireworks(5000)->shoot();

    return to_route('orders.index');
}
```

The client adapter reads the prop on `inertia:success` and is registered
automatically by the bundled runtime. If you import the runtime yourself, wire
it up:

```js
import { start } from '@laranail/confetti'

start({ inertiaProp: 'confetti' })
```

Running animations are aborted on `inertia:before`, so a fifteen-second effect
does not follow the visitor to the next page.

It is off by default because sharing a prop only helps if that adapter is
loaded. See [Transports](../tools/transports.md).

---

[← Docs index](../../README.md#documentation)
