# Testing

`Confetti::fake()` swaps the transport for a recorder, so a test can assert on
what an action fired without rendering a page.

## The basics

```php
use Simtabi\Laranail\Confetti\Facades\Confetti;

it('celebrates a completed order', function () {
    Confetti::fake();

    $this->post('/orders', ['id' => 1]);

    Confetti::assertFired();
});
```

## Assertions

| Assertion | Checks |
|---|---|
| `assertFired()` | Something fired |
| `assertFired(callable)` | Something matching the predicate fired |
| `assertFiredTimes(int)` | Exactly this many payloads |
| `assertNothingFired()` | Nothing fired |
| `assertAnimation(ConfettiAnimation\|string)` | A specific continuous effect was requested |
| `assertBurstCount(int)` | This many bursts in total |

```php
Confetti::fake();

Confetti::realistic()->shoot();

Confetti::assertFiredTimes(1);
Confetti::assertBurstCount(5);
Confetti::assertFired(fn ($payload) => $payload->bursts[0]->options['spread'] === 26.0);
```

Failures name what actually fired, because "expected confetti, got none" is slow
to debug when the effect is three layers down in a controller:

```
Expected the 'snow' animation, but only found: fireworks.
Expected no confetti to have been fired, but 6 burst(s) fired.
```

## The trait

```php
use Simtabi\Laranail\Confetti\Testing\InteractsWithConfetti;

uses(InteractsWithConfetti::class);

it('celebrates', function () {
    $this->fakeConfetti();

    $this->post('/orders');

    $this->confetti()->assertAnimation('fireworks');
});
```

## Asserting without faking

Nothing is sent until `shoot()`, so an effect can be inspected directly:

```php
$payload = Confetti::realistic()->toArray();

expect($payload['bursts'])->toHaveCount(5);
```

`toArray()` carries only what differs from the configured defaults, which makes
it small on the wire but awkward to assert against. `toResolvedArray()` merges
the defaults back in:

```php
$resolved = Confetti::count(50)->toResolvedArray();

expect($resolved['bursts'][0]['options'])->toMatchArray([
    'particleCount' => 50,
    'spread' => 70,   // from the defaults
    'ticks' => 200,
]);
```

## Testing a continuous effect

By default these ship as a descriptor, so there are no bursts to assert on:

```php
$payload = Confetti::snow()->toArray();

expect($payload['animations'])->toHaveCount(1);
expect($payload['animations'][0]['animation'])->toBe('snow');
```

For concrete bursts, expand with a seed — the output is then byte-for-byte
reproducible:

```php
$first = Confetti::snow(900)->seed(1234)->expand()->toArray();
$second = Confetti::snow(900)->seed(1234)->expand()->toArray();

unset($first['id'], $second['id']);   // deliberately unique per payload

expect($first)->toBe($second);
```

## Restoring

```php
Confetti::restore();
```

Puts the real transports back. Rarely needed — the container is rebuilt between
tests — but useful in a test that asserts on the faked state and then on real
behaviour.

## Testing the browser runtime

The runtime has its own Vitest suite under `tests/js`, with canvas-confetti
mocked. If you are extending it, that is the pattern:

```js
vi.mock('canvas-confetti', () => {
  const confetti = vi.fn(() => Promise.resolve())
  confetti.create = vi.fn(() => confetti)
  confetti.shapeFromText = vi.fn(() => ({ type: 'bitmap', bitmap: { close: vi.fn() } }))

  return { default: confetti }
})
```

---

[← Docs index](../../README.md#documentation)
