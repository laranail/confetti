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
    /**
     * The builder methods an effect definition may call.
     *
     * An effect describes what confetti looks like, so it may only reach
     * methods that configure one. `method_exists()` alone is not enough: the
     * builder also exposes `shoot()`, `via()`, `expand()`, `seed()` and
     * `reset()`, and a definition reaching those is not describing an effect,
     * it is deciding when confetti fires and which transport carries it.
     *
     * That distinction costs nothing while definitions live in a config file
     * written by a developer. It matters as soon as one does not, and
     * {@see register()} exists precisely so definitions can come from
     * elsewhere. An allowlist keeps the blast radius at "wrong-looking
     * confetti" rather than "unexpected control flow".
     */
    private const array CONFIGURATION_METHODS = [
        // Particles
        'count', 'spread', 'angle', 'startVelocity', 'decay', 'gravity',
        'drift', 'ticks', 'scalar', 'flat', 'zIndex', 'option',
        // Position
        'origin', 'originX', 'originY', 'position', 'center', 'top', 'bottom',
        'left', 'right', 'topLeft', 'topRight', 'bottomLeft', 'bottomRight',
        // Appearance
        'colors', 'palette', 'shapes', 'shapeFromPath', 'shapeFromText',
        // Timing
        'delay', 'duration', 'stagger',
        // Accessibility
        'disableForReducedMotion', 'reducedMotion', 'skipForReducedMotion',
        // Presets
        'preset', 'stars', 'success', 'magic', 'rain', 'realistic', 'emoji',
        'fireworks', 'snow', 'schoolPride',
    ];

    /** Keys that are not builder methods and would be silently ignored. */
    private const array ALIASES = [
        'colours' => 'colors',
    ];

    /** @param array<string, array<string, mixed>> $effects */
    public function __construct(
        private array $effects = [],
    ) {}

    /** @return list<string> */
    public static function allowedMethods(): array
    {
        return self::CONFIGURATION_METHODS;
    }

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

            // Checked against the allowlist rather than method_exists(), so a
            // definition cannot reach dispatch or control flow. The two cases
            // get different messages because they are different mistakes: one
            // is a typo, the other is asking an effect to do something an
            // effect does not do.
            if (! in_array($method, self::CONFIGURATION_METHODS, true)) {
                throw method_exists($builder, $method)
                    ? InvalidEffect::methodNotAllowed($name, $key)
                    : InvalidEffect::unknownOption($name, $key);
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
