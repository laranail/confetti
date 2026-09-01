<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Support;

use Simtabi\Laranail\Confetti\Exceptions\AssetNotBuilt;

/**
 * Locates the browser bundle and describes it.
 *
 * Two bundles ship: an IIFE for a plain `<script>` tag, and an ES module for
 * applications that import it into their own build.
 *
 * The content hash is what makes the `route` delivery mode safe to cache
 * forever: the URL changes when the bundle does, so a browser can hold onto it
 * indefinitely and still never serve a stale copy after an upgrade. xxh128 is
 * used because this runs on every page render and a cryptographic digest would
 * be wasted effort for a cache key.
 */
final class Assets
{
    public const string IIFE = 'confetti.js';

    public const string MODULE = 'confetti.mjs';

    /**
     * Public filename to the file on disk and its content type.
     *
     * A fixed map rather than a path built from the request, so the asset route
     * has no traversal surface at all.
     *
     * @var array<string, array{file: string, type: string}>
     */
    private const array FILES = [
        self::IIFE => ['file' => 'confetti.iife.js', 'type' => 'application/javascript'],
        self::MODULE => ['file' => 'confetti.esm.mjs', 'type' => 'text/javascript'],
    ];

    /** @var array<string, string> */
    private array $hashes = [];

    public function __construct(
        private readonly string $directory,
    ) {}

    public static function default(): self
    {
        return new self(dirname(__DIR__, 2).'/resources/dist');
    }

    /** @return list<string> */
    public static function filenames(): array
    {
        return array_keys(self::FILES);
    }

    public static function isKnown(string $filename): bool
    {
        return isset(self::FILES[$filename]);
    }

    public function path(string $filename = self::IIFE): ?string
    {
        if (! isset(self::FILES[$filename])) {
            return null;
        }

        return $this->directory.'/'.self::FILES[$filename]['file'];
    }

    public function contentType(string $filename = self::IIFE): string
    {
        return self::FILES[$filename]['type'] ?? 'application/javascript';
    }

    public function exists(string $filename = self::IIFE): bool
    {
        $path = $this->path($filename);

        return $path !== null && is_file($path);
    }

    /**
     * A short content hash, or `dev` when the bundle has not been built.
     *
     * Never throws; a missing bundle costs a page its confetti, and returning
     * a placeholder keeps the page itself rendering.
     */
    public function hash(string $filename = self::IIFE): string
    {
        if (isset($this->hashes[$filename])) {
            return $this->hashes[$filename];
        }

        $path = $this->path($filename);

        if ($path === null || ! is_file($path)) {
            return 'dev';
        }

        $hash = hash_file('xxh128', $path);

        return $this->hashes[$filename] = $hash === false ? 'dev' : substr($hash, 0, 12);
    }

    public function contents(string $filename = self::IIFE): string
    {
        $path = $this->path($filename);

        if ($path === null || ! is_file($path)) {
            throw AssetNotBuilt::at($path ?? $this->directory.'/'.$filename);
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw AssetNotBuilt::at($path);
        }

        return $contents;
    }

    public function size(string $filename = self::IIFE): int
    {
        $path = $this->path($filename);

        if ($path === null || ! is_file($path)) {
            return 0;
        }

        $size = filesize($path);

        return $size === false ? 0 : $size;
    }

    public function directory(): string
    {
        return $this->directory;
    }
}
