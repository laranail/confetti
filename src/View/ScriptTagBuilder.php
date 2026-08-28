<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\View;

use Throwable;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Container\Container;
use Simtabi\Laranail\Confetti\Support\Assets;
use Simtabi\Laranail\Confetti\Enums\AssetMode;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;

/**
 * Builds the `<script>` tag for the configured delivery mode.
 *
 * Kept out of the Blade view so every mode can be asserted in a unit test
 * without rendering a template. The failure this guards against is a mode that
 * throws only when someone switches to it in production.
 *
 * URLs are built from `config('app.url')`, never from the request. A `Host` or
 * `X-Forwarded-Host` header is attacker-controlled on many deployments, and
 * reflecting one into a `<script src>` would let a spoofed header decide where
 * the browser fetches executable code from.
 */
final readonly class ScriptTagBuilder
{
    public function __construct(
        private ConfettiConfig $config,
        private Assets $assets,
        private Container $container,
    ) {}

    public function render(): string
    {
        return match ($this->config->assetMode) {
            AssetMode::Route     => $this->tag($this->routeUrl()),
            AssetMode::Published => $this->tag($this->publishedUrl()),
            AssetMode::Cdn       => $this->cdnTag(),
            AssetMode::Vite      => $this->viteTag(),
            AssetMode::Off       => '',
        };
    }

    /** The URL the `route` mode serves the bundle from. */
    public function routeUrl(string $file = Assets::IIFE): string
    {
        $base = rtrim((string) config('app.url', ''), '/');
        $prefix = trim((string) $this->config->assetValue('route', '/vendor/confetti'), '/');

        return $base . '/' . $prefix . '/' . $file . '?id=' . $this->version($file);
    }

    public function publishedUrl(string $file = 'confetti.iife.js'): string
    {
        $base = rtrim((string) config('app.url', ''), '/');

        return $base . '/vendor/confetti/' . $file . '?id=' . $this->version();
    }

    /** The cache-busting string: an explicit version, else the content hash. */
    public function version(string $file = Assets::IIFE): string
    {
        $configured = $this->config->assetValue('version');

        return is_string($configured) && $configured !== ''
            ? $configured
            : $this->assets->hash($file);
    }

    private function cdnTag(): string
    {
        $url = $this->config->assetValue('cdn_url');

        if (! is_string($url) || $url === '') {
            Log::warning('laranail/confetti: assets.mode is "cdn" but no assets.cdn_url is configured.');

            return '';
        }

        $integrity = $this->config->assetValue('cdn_integrity');

        return $this->tag(
            $url,
            is_string($integrity) && $integrity !== ''
                ? ['integrity' => $integrity, 'crossorigin' => 'anonymous']
                : [],
        );
    }

    /**
     * Resolve the bundle from the application's own Vite manifest.
     *
     * Hand-rolled because the package builder this sits on has no Vite support.
     * A missing manifest entry throws in Laravel, so it is caught and downgraded
     * to the route mode; losing a build optimisation is a better outcome than a
     * 500 on every page.
     */
    private function viteTag(): string
    {
        $entry = (string) $this->config->assetValue('vite_entry', 'resources/js/confetti.js');

        try {
            $vite = $this->container->make(Vite::class);

            return (string) $vite($entry);
        } catch (Throwable $e) {
            Log::warning('laranail/confetti: falling back to the route asset mode.', [
                'entry'  => $entry,
                'reason' => $e->getMessage(),
            ]);

            return $this->tag($this->routeUrl());
        }
    }

    /** @param array<string, string> $extra */
    private function tag(string $src, array $extra = []): string
    {
        $attributes = [
            'type' => 'module',
            'src'  => $src,
            ...$extra,
        ];

        if ($this->config->cspNonce !== null) {
            $attributes['nonce'] = $this->config->cspNonce;
        }

        $rendered = '';

        foreach ($attributes as $name => $value) {
            $rendered .= sprintf(' %s="%s"', $name, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }

        if ($this->config->assetValue('defer', true)) {
            $rendered .= ' defer';
        }

        return '<script' . $rendered . '></script>';
    }
}
