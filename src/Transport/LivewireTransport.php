<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Transport;

use Throwable;
use Illuminate\Contracts\Container\Container;
use Simtabi\Laranail\Confetti\Contracts\Transport;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;

/**
 * Dispatches a browser event from the Livewire component handling the request.
 *
 * Livewire forwards dispatched events to the browser as `window` events, so the
 * runtime picks them up without Alpine and without a page load, which is what
 * makes confetti work on a component action rather than only after a redirect.
 *
 * Everything here is duck-typed through string class names and `is_callable`,
 * so `livewire/livewire` stays a development dependency. Two reasons that
 * matters: applications that do not use Livewire should not be made to install
 * it, and `isLivewireRequest()` and `current()` are manager internals that have
 * moved between major versions. If either disappears, this transport reports
 * itself unavailable and the session transport takes over, so confetti arrives a
 * navigation later instead of not at all.
 *
 * The payload is dispatched as a single object argument rather than positional
 * parameters, so the runtime reads `event.detail` directly.
 */
final readonly class LivewireTransport implements Transport
{
    private const string MANAGER = 'Livewire\\Livewire';

    public function __construct(
        private Container $container,
        private string $event,
        private bool $enabled = true,
    ) {}

    public function name(): string
    {
        return 'livewire';
    }

    public function available(): bool
    {
        // Turning the integration off makes a Livewire request fall through to
        // the session transport, so confetti still arrives, just a navigation
        // later. Useful when a component dispatches so much that the extra
        // browser event is unwelcome.
        return $this->enabled
            && $this->isLivewireRequest()
            && $this->currentComponent() !== null;
    }

    public function send(ConfettiPayload $payload): void
    {
        $component = $this->currentComponent();

        if ($component === null || ! is_callable([$component, 'dispatch'])) {
            return;
        }

        $component->dispatch($this->event, $payload->toArray());
    }

    private function isLivewireRequest(): bool
    {
        // The array form is required, not stylistic: self::MANAGER is a class
        // that may not exist, so the call has to stay dynamic. A first-class
        // callable here would bind to this class instead and recurse.
        if (! class_exists(self::MANAGER) || ! is_callable([self::MANAGER, 'isLivewireRequest'])) {
            return false;
        }

        try {
            return (bool) call_user_func([self::MANAGER, 'isLivewireRequest']);
        } catch (Throwable) {
            return false;
        }
    }

    private function currentComponent(): ?object
    {
        if (! $this->container->bound('livewire')) {
            return null;
        }

        try {
            $manager = $this->container->make('livewire');

            if (! is_object($manager) || ! is_callable([$manager, 'current'])) {
                return null;
            }

            $component = $manager->current();
        } catch (Throwable) {
            return null;
        }

        return is_object($component) ? $component : null;
    }
}
