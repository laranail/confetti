<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Presets;

use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Exceptions\InvalidPreset;

/**
 * Maps preset names to the objects that implement them.
 *
 * Factories rather than instances, because three of the presets take arguments
 * (a duration, or the emoji to throw) and each call needs its own.
 *
 * Applications can add their own:
 *
 *     Confetti::registerPreset('brand', fn () => new BrandPreset);
 *     Confetti::preset('brand')->shoot();
 */
final class PresetRegistry
{
    /** @var array<string, callable(mixed...): Preset> */
    private array $factories = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    /** @param callable(mixed...): Preset $factory */
    public function register(string $name, callable $factory): self
    {
        $this->factories[$name] = $factory;

        return $this;
    }

    /** @throws InvalidPreset */
    public function make(string $name, mixed ...$args): Preset
    {
        if (! isset($this->factories[$name])) {
            throw InvalidPreset::unknown($name, $this->names());
        }

        return ($this->factories[$name])(...$args);
    }

    public function has(string $name): bool
    {
        return isset($this->factories[$name]);
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->factories);
    }

    private function registerDefaults(): void
    {
        $this->register('stars', static fn (): Preset => new StarsPreset);
        $this->register('success', static fn (): Preset => new SuccessPreset);
        $this->register('magic', static fn (): Preset => new MagicPreset);
        $this->register('rain', static fn (): Preset => new RainPreset);
        $this->register('realistic', static fn (): Preset => new RealisticPreset);
        $this->register('emoji', static fn (string $text = '🦄'): Preset => new EmojiPreset($text));
        $this->register('fireworks', static fn (?int $duration = null): Preset => new FireworksPreset($duration));
        $this->register('snow', static fn (?int $duration = null): Preset => new SnowPreset($duration));
        $this->register('schoolPride', static fn (?int $duration = null): Preset => new SchoolPridePreset($duration));
    }
}
