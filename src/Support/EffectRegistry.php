<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Support;

use Simtabi\Laranail\Confetti\Builder\ConfettiBuilder;
use Simtabi\Laranail\Confetti\Exceptions\InvalidEffect;

/**
 * Named confetti configurations, declared in config rather than in code.
 *
 * A preset is a shipped effect written in PHP. An effect is an application's
 * own combination of options, given a name so the call site says what it means
 * rather than how it looks:
 *
 *     'effects' => [
 *         'checkout' => ['preset' => 'realistic'],
 *         'signup'   => ['preset' => 'stars', 'palette' => 'brand'],
 *         'subtle'   => ['count' => 40, 'spread' => 45, 'position' => 'top'],
 *     ],
 *
 *     Confetti::effect('checkout')->shoot();
 *
 * That indirection is the point. Designers change what "checkout" looks like by
 * editing config; the controller keeps saying `effect('checkout')`.
 *
 * Each key maps to a builder method, so anything the builder can do an effect
 * can declare. Values are passed straight through, and a list is spread as
 * separate arguments so `'origin' => [0.5, 0.7]` works.
 */
final class EffectRegistry
{
    /** Keys that are not builder methods and would be silently ignored. */
    private const array ALIASES = [
        'colours' => 'colors',
    ];

    /** @param array<string, array<string, mixed>> $effects */
    public function __construct(
        private array $effects = [],
    ) {}

    /**
     * Add an effect at runtime, for a package or a provider that ships its own.
     *
     * @param array<string, mixed> $definition
     */
    public function register(string $name, array $definition): self
    {
        $this->effects[$name] = $definition;

        return $this;
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->effects);
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->effects);
    }

    /** @return array<string, mixed> */
    public function definition(string $name): array
    {
        if (! $this->has($name)) {
            throw InvalidEffect::unknown($name, $this->names());
        }

        return $this->effects[$name];
    }

    /**
     * Apply a named effect to a builder.
     *
     * @throws InvalidEffect
     */
    public function apply(string $name, ConfettiBuilder $builder): ConfettiBuilder
    {
        foreach ($this->definition($name) as $key => $value) {
            $method = self::ALIASES[$key] ?? $key;

            if (! method_exists($builder, $method)) {
                throw InvalidEffect::unknownOption($name, $key);
            }

            // A list becomes separate arguments, so origin: [0.5, 0.7] reaches
            // origin(0.5, 0.7). An associative array is one argument, which is
            // what colors: ['#fff', '#000'] needs.
            $arguments = is_array($value) && array_is_list($value) ? $value : [$value];

            $builder->{$method}(...$arguments);
        }

        return $builder;
    }
}
