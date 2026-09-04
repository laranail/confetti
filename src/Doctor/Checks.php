<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Doctor;

use Throwable;
use Illuminate\Foundation\Vite;
use Illuminate\Contracts\Container\Container;
use Simtabi\Laranail\Confetti\Support\Assets;
use Simtabi\Laranail\Confetti\Enums\AssetMode;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorCheck;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorResult;
use Simtabi\Laranail\Package\Tools\Services\Doctor\Checks\CallbackCheck;

/**
 * Diagnostics for the things that fail quietly.
 *
 * Every check here corresponds to a way confetti can be configured into a state
 * where nothing appears and nothing errors: an unbuilt bundle, a CDN mode with
 * no URL, a Vite entry that is not in the manifest, a custom canvas whose
 * z-index the library silently ignores.
 */
final readonly class Checks
{
    public const string OK = 'ok';

    public const string WARN = 'warning';

    public const string FAIL = 'failed';

    public function __construct(
        private ConfettiConfig $config,
        private Assets $assets,
        private Container $container,
    ) {}

    /**
     * The checks, as the shared doctor subsystem consumes them.
     *
     * Each private method still answers the array it always did; this wraps them
     * so `DoctorReporter` renders confetti the same way it renders every other
     * package, and so `--json` and the exit code come for free rather than being
     * re-implemented in the command.
     *
     * @return list<DoctorCheck>
     */
    public function all(): array
    {
        return array_map(
            static fn (array $raw): DoctorCheck => new CallbackCheck(
                $raw['name'],
                $raw['name'],
                static fn (): DoctorResult => match ($raw['status']) {
                    self::FAIL => DoctorResult::fail($raw['message']),
                    self::WARN => DoctorResult::warn($raw['message']),
                    default    => DoctorResult::pass($raw['message']),
                },
            ),
            $this->raw(),
        );
    }

    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    public function raw(): array
    {
        return [
            $this->bundle(),
            $this->assetMode(),
            $this->canvas(),
            $this->worker(),
            $this->expansion(),
            $this->stacks(),
        ];
    }

    /** @return array{name: string, status: string, message: string} */
    private function bundle(): array
    {
        if (! $this->assets->exists()) {
            return $this->result('Browser bundle', self::FAIL, sprintf(
                'Missing at %s. It ships with the package, so this usually means an incomplete '
                . 'checkout. Run `npm install && npm run build` in the package directory.',
                $this->assets->directory(),
            ));
        }

        return $this->result('Browser bundle', self::OK, sprintf(
            'Built: %s, %s (hash %s).',
            Assets::IIFE,
            $this->humanBytes($this->assets->size()),
            $this->assets->hash(),
        ));
    }

    /** @return array{name: string, status: string, message: string} */
    private function assetMode(): array
    {
        $mode = $this->config->assetMode;

        return match ($mode) {
            AssetMode::Route => $this->result(
                'Asset delivery',
                self::OK,
                'Served from a content-hashed route. No publish step, and a stale bundle is impossible.',
            ),

            AssetMode::Published => is_file(public_path('vendor/confetti/confetti.iife.js'))
                ? $this->result('Asset delivery', self::OK, 'Published to public/vendor/confetti.')
                : $this->result(
                    'Asset delivery',
                    self::FAIL,
                    'Mode is "published" but public/vendor/confetti/confetti.iife.js is missing. '
                    . 'Run `php artisan vendor:publish --tag=laranail::confetti-assets`.',
                ),

            AssetMode::Cdn => is_string($this->config->assetValue('cdn_url'))
                && $this->config->assetValue('cdn_url') !== ''
                    ? $this->result('Asset delivery', self::OK, 'Loading from ' . $this->config->assetValue('cdn_url') . '.')
                    : $this->result(
                        'Asset delivery',
                        self::FAIL,
                        'Mode is "cdn" but assets.cdn_url is empty, so no script tag is emitted at all.',
                    ),

            AssetMode::Vite => $this->viteEntry(),

            AssetMode::Off => $this->result(
                'Asset delivery',
                self::WARN,
                'Mode is "off": no script tag is emitted. The application must load the runtime itself, '
                . 'or nothing will fire.',
            ),
        };
    }

    /** @return array{name: string, status: string, message: string} */
    private function viteEntry(): array
    {
        $entry = (string) $this->config->assetValue('vite_entry', 'resources/js/confetti.js');

        try {
            $vite = $this->container->make(Vite::class);
            $vite($entry);

            return $this->result('Asset delivery', self::OK, "Resolved '{$entry}' from the Vite manifest.");
        } catch (Throwable $e) {
            return $this->result('Asset delivery', self::WARN, sprintf(
                "Mode is \"vite\" but '%s' could not be resolved (%s). Falling back to the route mode at runtime.",
                $entry,
                $e->getMessage(),
            ));
        }
    }

    /** @return array{name: string, status: string, message: string} */
    private function canvas(): array
    {
        $canvas = $this->config->runtimeValue('canvas');

        if (! is_string($canvas) || $canvas === '') {
            return $this->result('Canvas', self::OK, 'Using the library\'s own canvas.');
        }

        $zIndex = $this->config->defaults['zIndex'] ?? 100;

        if ((int) $zIndex !== 100) {
            return $this->result('Canvas', self::WARN, sprintf(
                'runtime.canvas is set to "%s" and defaults.zIndex to %d. canvas-confetti ignores zIndex on a '
                . 'canvas it did not create, so the runtime applies the positioning and stacking itself. Check '
                . 'the element is not clipped by an ancestor.',
                $canvas,
                (int) $zIndex,
            ));
        }

        return $this->result('Canvas', self::OK, "Drawing onto \"{$canvas}\".");
    }

    /** @return array{name: string, status: string, message: string} */
    private function worker(): array
    {
        if (! $this->config->runtimeValue('use_worker', true)) {
            return $this->result('Web worker', self::OK, 'Disabled; rendering on the main thread.');
        }

        return $this->result(
            'Web worker',
            self::OK,
            'Enabled. The worker is built from a blob URL, so a strict Content-Security-Policy needs '
            . '`worker-src blob:`. Without it canvas-confetti logs a warning and falls back to the main thread.',
        );
    }

    /** @return array{name: string, status: string, message: string} */
    private function expansion(): array
    {
        if ($this->config->expansion->value === 'server') {
            return $this->result(
                'Preset expansion',
                self::WARN,
                'Set to "server": snow, fireworks and schoolPride are expanded into hundreds of bursts in PHP '
                . 'and shipped in full. Expect payloads in the hundreds of kilobytes, and identical randomness '
                . 'for every visitor. "client" is the default for a reason.',
            );
        }

        return $this->result(
            'Preset expansion',
            self::OK,
            'Continuous effects ship as descriptors and run in the browser.',
        );
    }

    /** @return array{name: string, status: string, message: string} */
    private function stacks(): array
    {
        $detected = [];

        foreach ([
            'Livewire' => 'Livewire\\Livewire',
            'Filament' => 'Filament\\Facades\\Filament',
            'Inertia'  => 'Inertia\\Inertia',
        ] as $label => $class) {
            if (class_exists($class)) {
                $detected[] = $label;
            }
        }

        return $this->result('Detected stacks', self::OK, $detected === []
            ? 'None. The session transport will carry payloads across redirects.'
            : implode(', ', $detected) . '.');
    }

    /** @return array{name: string, status: string, message: string} */
    private function result(string $name, string $status, string $message): array
    {
        return ['name' => $name, 'status' => $status, 'message' => $message];
    }

    private function humanBytes(int $bytes): string
    {
        return $bytes < 1024
            ? $bytes . ' B'
            : round($bytes / 1024, 1) . ' KB';
    }
}
