<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Payload;

use JsonSerializable;
use Simtabi\Laranail\Confetti\Enums\ConfettiAnimation;

/**
 * An instruction to run a continuous effect in the browser.
 *
 * This is the alternative to shipping the effect as hundreds of bursts. Snow at
 * its default duration is roughly five hundred `confetti()` calls; expanded
 * server-side that is around 150 KB of JSON on every response, with the
 * randomness already decided so every visitor watches the same snowfall. As a
 * descriptor it is about 250 bytes and each browser rolls its own.
 *
 * `options` are canvas-confetti options applied to every particle the loop
 * emits. `params` are the loop's own knobs (interval, ranges, decay curves)
 * expressed as `[min, max]` pairs where a value is randomised per frame. Keeping
 * them separate is what lets the runtime stay a faithful port of the upstream
 * recipe while still being configurable from PHP.
 */
final readonly class Animation implements JsonSerializable
{
    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $params
     */
    public function __construct(
        public ConfettiAnimation $animation,
        public int $duration,
        public array $options = [],
        public array $params = [],
    ) {}

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $params
     */
    public static function make(
        ConfettiAnimation $animation,
        ?int $duration = null,
        array $options = [],
        array $params = [],
    ): self {
        return new self(
            animation: $animation,
            duration: $duration ?? $animation->defaultDuration(),
            options: $options,
            params: $params,
        );
    }

    /** @param array{animation: string, duration?: int, options?: array<string, mixed>, params?: array<string, mixed>} $data */
    public static function fromArray(array $data): self
    {
        $animation = ConfettiAnimation::from($data['animation']);

        return new self(
            animation: $animation,
            duration: (int) ($data['duration'] ?? $animation->defaultDuration()),
            options: $data['options'] ?? [],
            params: $data['params'] ?? [],
        );
    }

    /** @param array<string, mixed> $options */
    public function withOptions(array $options): self
    {
        return new self($this->animation, $this->duration, $options, $this->params);
    }

    public function withDuration(int $duration): self
    {
        return new self($this->animation, $duration, $this->options, $this->params);
    }

    /** @return array{animation: string, duration: int, options: array<string, mixed>, params: array<string, mixed>} */
    public function toArray(): array
    {
        return [
            'animation' => $this->animation->value,
            'duration'  => $this->duration,
            'options'   => $this->options,
            'params'    => $this->params,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
