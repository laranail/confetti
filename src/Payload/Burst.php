<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Payload;

use JsonSerializable;

/**
 * One discrete `confetti()` call, plus how long to wait before making it.
 *
 * `options` holds only the values that differ from the package defaults. The
 * defaults themselves travel once, in the boot payload, and the runtime merges
 * the two in the same order PHP does:
 *
 *     canvas-confetti built-ins  <  boot defaults  <  burst options
 *
 * A five-burst `realistic()` therefore ships five short objects rather than five
 * copies of the full option set.
 */
final readonly class Burst implements JsonSerializable
{
    /** @param array<string, mixed> $options */
    public function __construct(
        public array $options = [],
        public int $delay = 0,
    ) {}

    /** @param array<string, mixed> $options */
    public static function make(array $options = [], int $delay = 0): self
    {
        return new self($options, $delay);
    }

    /** @param array<string, mixed> $options */
    public function with(array $options): self
    {
        return new self([...$this->options, ...$options], $this->delay);
    }

    public function delayedBy(int $delay): self
    {
        return new self($this->options, $delay);
    }

    /** @return array{delay: int, options: array<string, mixed>} */
    public function toArray(): array
    {
        return [
            'delay' => $this->delay,
            'options' => $this->options,
        ];
    }

    /** @param array{delay?: int, options?: array<string, mixed>} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            options: $data['options'] ?? [],
            delay: (int) ($data['delay'] ?? 0),
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
