<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Transport;

use Throwable;
use Illuminate\Http\Request;
use Simtabi\Laranail\Confetti\Contracts\Transport;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;

/**
 * Shares the payload as a prop on the current Inertia response.
 *
 * An Inertia visit returns JSON, not HTML, so there is no `</body>` to inject
 * into and no full page load to carry a session flash. The payload rides along
 * as a page prop instead, and the client adapter fires it on `inertia:success`.
 *
 * Duck-typed for the same reasons as the Livewire transport: Inertia stays a
 * development dependency, and an unavailable transport degrades to the session
 * one rather than failing.
 *
 * Off by default: sharing a prop only helps if the client adapter is loaded, so
 * enable `integrations.inertia` deliberately.
 */
final readonly class InertiaTransport implements Transport
{
    private const string FACADE = 'Inertia\\Inertia';

    public function __construct(
        private Request $request,
        private string $prop,
        private bool $enabled = true,
    ) {}

    public function name(): string
    {
        return 'inertia';
    }

    public function available(): bool
    {
        // The array form is required, not stylistic: self::FACADE is a class
        // that may not exist, so the call has to stay dynamic. A first-class
        // callable here would bind to this class instead.
        return $this->enabled
            && class_exists(self::FACADE)
            && is_callable([self::FACADE, 'share'])
            && $this->request->hasHeader('X-Inertia');
    }

    public function send(ConfettiPayload $payload): void
    {
        if (! class_exists(self::FACADE) || ! is_callable([self::FACADE, 'share'])) {
            return;
        }

        try {
            call_user_func([self::FACADE, 'share'], $this->prop, $payload->toArray());
        } catch (Throwable) {
            // Sharing a prop is best-effort; a decorative effect must never be
            // the reason a response fails to render.
        }
    }
}
