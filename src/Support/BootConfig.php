<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Support;

use Throwable;
use Illuminate\Contracts\Container\Container;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;

/**
 * Everything the browser runtime needs, assembled once per page.
 *
 * Emitted as a `<script type="application/json">` block of data, which the
 * browser never executes, so no CSP allowance is needed and the runtime can be
 * a plain external module with no framework behind it. That is what replaced
 * the Alpine component the payload used to ride in on, and why this package no
 * longer needs Alpine at all.
 *
 * The default options travel here, once, rather than being repeated inside
 * every burst.
 */
final readonly class BootConfig
{
    public function __construct(
        private ConfettiConfig $config,
        private Container $container,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'event'       => $this->config->event,
            'legacyEvent' => $this->config->legacyEvent,
            'defaults'    => $this->serialisableDefaults(),
            'runtime'     => [
                'useWorker'               => (bool) $this->config->runtimeValue('use_worker', true),
                'canvas'                  => $this->config->runtimeValue('canvas'),
                'reducedMotion'           => $this->config->reducedMotion->value,
                'pauseWhenHidden'         => (bool) $this->config->runtimeValue('pause_when_hidden', true),
                'maxConcurrentAnimations' => (int) $this->config->runtimeValue('max_concurrent_animations', 3),
                'shapeCacheSize'          => (int) $this->config->runtimeValue('shape_cache_size', 32),
                'debug'                   => (bool) $this->config->runtimeValue('debug', false),
            ],
            'payload' => $this->pendingPayload(),
        ];
    }

    public function toJson(): string
    {
        return Json::encode($this->toArray());
    }

    /**
     * The payload flashed by the previous request, if any.
     *
     * A plain read. Unlike writing, reading a flashed key does not extend its
     * life, so this cannot resurrect an effect into the following request.
     *
     * @return array<string, mixed>|null
     */
    private function pendingPayload(): ?array
    {
        if (! $this->container->bound('session.store')) {
            return null;
        }

        try {
            $session = $this->container->make('session.store');

            if (! $session->isStarted()) {
                return null;
            }

            $payload = $session->get($this->config->sessionKey);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($payload) || $payload === []) {
            return null;
        }

        // Normalise, so a payload flashed before an upgrade still arrives in the
        // shape the current runtime expects.
        return ConfettiPayload::fromArray($payload)->toArray();
    }

    /**
     * Defaults with shape objects flattened to their wire form.
     *
     * @return array<string, mixed>
     */
    private function serialisableDefaults(): array
    {
        $defaults = $this->config->resolvedDefaults();

        if (isset($defaults['shapes']) && is_array($defaults['shapes'])) {
            $defaults['shapes'] = array_map(
                static fn (mixed $shape): mixed => is_object($shape) && method_exists($shape, 'toWire')
                    ? $shape->toWire()
                    : $shape,
                array_values($defaults['shapes']),
            );
        }

        return $defaults;
    }
}
