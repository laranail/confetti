<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Validation;

/**
 * The configurable ceilings on confetti input.
 *
 * These are not canvas-confetti constraints — the library will happily accept a
 * million particles and then spend the frame budget on them. They exist so a
 * value threaded through from user input cannot turn a decorative effect into a
 * denial of service on the visitor's own browser.
 */
final readonly class Limits
{
    private const array DEFAULTS = [
        'max_particles' => 1000,
        'max_ticks' => 2000,
        'max_delay' => 60000,
        'max_duration' => 60000,
        'max_bursts' => 200,
    ];

    public function __construct(
        public int $maxParticles = self::DEFAULTS['max_particles'],
        public int $maxTicks = self::DEFAULTS['max_ticks'],
        public int $maxDelay = self::DEFAULTS['max_delay'],
        public int $maxDuration = self::DEFAULTS['max_duration'],
        public int $maxBursts = self::DEFAULTS['max_bursts'],
    ) {}

    /** @param array<string, mixed> $config */
    public static function fromArray(array $config): self
    {
        $read = static fn (string $key): int => (int) ($config[$key] ?? self::DEFAULTS[$key]);

        return new self(
            maxParticles: $read('max_particles'),
            maxTicks: $read('max_ticks'),
            maxDelay: $read('max_delay'),
            maxDuration: $read('max_duration'),
            maxBursts: $read('max_bursts'),
        );
    }

    public static function default(): self
    {
        return new self;
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'max_particles' => $this->maxParticles,
            'max_ticks' => $this->maxTicks,
            'max_delay' => $this->maxDelay,
            'max_duration' => $this->maxDuration,
            'max_bursts' => $this->maxBursts,
        ];
    }
}
