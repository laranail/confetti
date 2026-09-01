<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Support;

use Simtabi\Laranail\Confetti\Enums\AssetMode;
use Simtabi\Laranail\Confetti\Enums\PresetExpansion;
use Simtabi\Laranail\Confetti\Enums\ReducedMotionPolicy;
use Simtabi\Laranail\Confetti\Enums\TransportDriver;
use Simtabi\Laranail\Confetti\Exceptions\InvalidColor;
use Simtabi\Laranail\Confetti\Validation\ColorValidator;
use Simtabi\Laranail\Confetti\Validation\Limits;

/**
 * A typed reading of `config('laranail.confetti')`.
 *
 * Resolved once and shared, so the config array is parsed and coerced in one
 * place rather than at every call site. Everything that reads configuration
 * (the builder, the transports, the script tag, the boot payload) goes through
 * here, which also means a test can construct one directly instead of writing
 * to the config repository.
 */
final readonly class ConfettiConfig
{
    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, list<string>>  $palettes
     * @param  array<string, mixed>  $assets
     * @param  array<string, mixed>  $inject
     * @param  array<string, mixed>  $runtime
     * @param  array<string, mixed>  $integrations
     */
    public function __construct(
        public bool $enabled = true,
        public string $event = 'confetti:fire',
        public ?string $legacyEvent = 'fire-confetti',
        public TransportDriver $transport = TransportDriver::Auto,
        public string $sessionKey = 'laranail.confetti',
        public string $inertiaProp = 'confetti',
        public array $defaults = [],
        public array $palettes = [],
        public PresetExpansion $expansion = PresetExpansion::Client,
        public int $presetDuration = 15000,
        public ?int $seed = null,
        public AssetMode $assetMode = AssetMode::Route,
        public array $assets = [],
        public array $inject = [],
        public ?string $cspNonce = null,
        public ReducedMotionPolicy $reducedMotion = ReducedMotionPolicy::Reduce,
        public array $runtime = [],
        public Limits $limits = new Limits,
        public bool $strict = true,
        public array $integrations = [],
        public array $effects = [],
    ) {}

    /** @param array<string, mixed> $config */
    public static function fromArray(array $config): self
    {
        /** @var array<string, mixed> $transportConfig */
        $transportConfig = self::section($config, 'transport');
        /** @var array<string, mixed> $presets */
        $presets = self::section($config, 'presets');
        /** @var array<string, mixed> $assets */
        $assets = self::section($config, 'assets');
        /** @var array<string, mixed> $runtime */
        $runtime = self::section($config, 'runtime');
        /** @var array<string, mixed> $security */
        $security = self::section($config, 'security');
        /** @var array<string, mixed> $validation */
        $validation = self::section($config, 'validation');

        $seed = $presets['seed'] ?? null;

        return new self(
            enabled: (bool) ($config['enabled'] ?? true),
            event: (string) ($config['event'] ?? 'confetti:fire'),
            legacyEvent: self::nullableString($config['legacy_event'] ?? 'fire-confetti'),
            transport: TransportDriver::coerce(self::stringOr($transportConfig['driver'] ?? null, 'auto'))
                ?? TransportDriver::Auto,
            sessionKey: (string) ($transportConfig['session_key'] ?? 'laranail.confetti'),
            inertiaProp: (string) ($transportConfig['inertia_prop'] ?? 'confetti'),
            defaults: self::section($config, 'defaults'),
            palettes: self::normalisePalettes(self::section($config, 'palettes')),
            expansion: PresetExpansion::coerce(self::stringOr($presets['expansion'] ?? null, 'client'))
                ?? PresetExpansion::Client,
            presetDuration: (int) ($presets['duration'] ?? 15000),
            seed: is_numeric($seed) ? (int) $seed : null,
            assetMode: AssetMode::coerce(self::stringOr($assets['mode'] ?? null, 'route')) ?? AssetMode::Route,
            assets: $assets,
            inject: self::section($config, 'inject'),
            cspNonce: self::nullableString($security['csp_nonce'] ?? null),
            reducedMotion: ReducedMotionPolicy::coerce(self::stringOr($runtime['reduced_motion'] ?? null, 'reduce'))
                ?? ReducedMotionPolicy::Reduce,
            runtime: $runtime,
            limits: Limits::fromArray(self::section($config, 'limits')),
            strict: (bool) ($validation['strict'] ?? true),
            integrations: self::section($config, 'integrations'),
            effects: self::section($config, 'effects'),
        );
    }

    /**
     * The default colour palette, falling back to canvas-confetti's own seven
     * colours when nothing is configured.
     *
     * @return list<string>
     */
    public function defaultColors(): array
    {
        $colors = $this->defaults['colors'] ?? null;

        if (is_array($colors) && $colors !== []) {
            return ColorValidator::validateAll(array_values($colors), strict: false);
        }

        return ['#26ccff', '#a25afd', '#ff5e7e', '#88ff5a', '#fcff42', '#ffa62d', '#ff36ff'];
    }

    /**
     * @return list<string>
     *
     * @throws InvalidColor
     */
    public function palette(string $name): array
    {
        if (! array_key_exists($name, $this->palettes)) {
            throw InvalidColor::unknownPalette($name, array_keys($this->palettes));
        }

        $palette = $this->palettes[$name];

        return $palette === [] ? $this->defaultColors() : $palette;
    }

    /** @return array<string, mixed> */
    public function resolvedDefaults(): array
    {
        return [...$this->defaults, 'colors' => $this->defaultColors()];
    }

    public function runtimeValue(string $key, mixed $fallback = null): mixed
    {
        return $this->runtime[$key] ?? $fallback;
    }

    public function integrationEnabled(string $name, bool $fallback = true): bool
    {
        $integration = $this->integrations[$name] ?? null;

        if (is_bool($integration)) {
            return $integration;
        }

        if (is_array($integration)) {
            return (bool) ($integration['enabled'] ?? $fallback);
        }

        return $fallback;
    }

    /** @return array<string, mixed> */
    public function integration(string $name): array
    {
        $integration = $this->integrations[$name] ?? [];

        return is_array($integration) ? $integration : [];
    }

    public function assetValue(string $key, mixed $fallback = null): mixed
    {
        return $this->assets[$key] ?? $fallback;
    }

    public function injectValue(string $key, mixed $fallback = null): mixed
    {
        return $this->inject[$key] ?? $fallback;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private static function section(array $config, string $key): array
    {
        $section = $config[$key] ?? [];

        return is_array($section) ? $section : [];
    }

    /**
     * @param  array<string, mixed>  $palettes
     * @return array<string, list<string>>
     */
    private static function normalisePalettes(array $palettes): array
    {
        $normalised = [];

        foreach ($palettes as $name => $colors) {
            // A null palette means "use the default colours"; represented as an
            // empty list here and resolved in palette().
            $normalised[(string) $name] = is_array($colors)
                ? ColorValidator::validateAll(array_values($colors), strict: false)
                : [];
        }

        return $normalised;
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function stringOr(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }
}
