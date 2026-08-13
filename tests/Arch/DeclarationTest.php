<?php

declare(strict_types=1);

/**
 * Nothing may be declared that nothing reads.
 *
 * Two settings shipped that were documented, published in the config file, and
 * never read by anything: `integrations.livewire.enabled` and `runtime.debug`.
 * A third, `integrations.filament.use_filament_assets`, was described in the
 * documentation and did not exist in the config file at all. All three are the
 * same defect wearing different clothes, and all three are invisible to a type
 * checker, because a config key is just a string until someone looks it up.
 *
 * So the three declarations are checked against each other: the config file,
 * the code that reads it, and the documentation that describes it. Any of the
 * three disagreeing fails the run.
 *
 * One limit, worth knowing before trusting this too far. The reader check
 * matches on the key's own name, so it catches a key added with a name nothing
 * looks up, and it catches documentation inventing a setting. It cannot catch a
 * key whose name is common vocabulary quietly losing its only reader:
 * `integrations.livewire.enabled` would still find the word `enabled`
 * elsewhere in the codebase. Those toggles are covered behaviourally instead,
 * in tests/Feature/IntegrationTogglesTest.php.
 */
$root = dirname(__DIR__, 2);

/** @param list<string> $sections */
function isDataSection(string $path, array $sections): bool
{
    return array_any($sections, fn (string $section): bool => str_starts_with($path, $section));
}

/**
 * Every leaf key in the config file, as a dotted path.
 *
 * @return list<string>
 */
$configKeys = static function () use ($root): array {
    /** @var array<string, mixed> $config */
    $config = require $root.'/config/confetti.php';

    $walk = static function (array $node, string $prefix) use (&$walk): array {
        $keys = [];

        foreach ($node as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $path = $prefix === '' ? $key : "{$prefix}.{$key}";

            // A nested list, such as a palette's colours, is a value rather
            // than a group of settings.
            if (is_array($value) && $value !== [] && array_keys($value) !== range(0, count($value) - 1)) {
                $keys = [...$keys, ...$walk($value, $path)];

                continue;
            }

            $keys[] = $path;
        }

        return $keys;
    };

    return $walk($config, '');
};

/** The code that could plausibly read a setting. */
$readers = static function () use ($root): string {
    $text = '';

    foreach (['src', 'resources/js', 'routes'] as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$dir));

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php', 'js'], true)) {
                $text .= file_get_contents($file->getPathname());
            }
        }
    }

    return $text;
};

/**
 * Config keys that are data rather than settings, so nothing looks them up by
 * name. Each is reached by iterating its parent.
 *
 * @var list<string>
 */
$dataKeys = [
    // Palette names. Consumers add their own, so the set cannot be closed.
    'palettes.default', 'palettes.success', 'palettes.magic',
    'palettes.gold', 'palettes.snow', 'palettes.pride',
    // Read as a whole array by the option stack, never individually.
    'defaults.particleCount', 'defaults.spread', 'defaults.ticks',
    'defaults.shapes', 'defaults.colors', 'defaults.zIndex',
    'defaults.disableForReducedMotion',
    // Matched as glob patterns against the request path.
    'inject.only', 'inject.except',
];

/**
 * Whole sections that are the application's own data rather than the package's
 * settings. Their keys are chosen by whoever edits the config, so there is no
 * fixed set to check against, and the shipped entries are examples.
 *
 * @var list<string>
 */
$dataSections = ['effects.', 'palettes.'];

it('has a reader for every setting in the config file', function () use ($configKeys, $readers, $dataKeys): void {
    $code = $readers();
    $orphans = [];

    foreach ($configKeys() as $path) {
        if (in_array($path, $dataKeys, true)) {
            continue;
        }

        // A top-level key has no dot, and strrpos returns false there rather
        // than a position, so it cannot be cast and offset blindly.
        $leaf = str_contains($path, '.')
            ? substr($path, (int) strrpos($path, '.') + 1)
            : $path;

        // Either the snake_case key as written, or the camelCase name it takes
        // in the boot payload the browser runtime reads.
        $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $leaf))));

        foreach ([$leaf, $camel] as $needle) {
            if (str_contains($code, "'{$needle}'") || str_contains($code, "\"{$needle}\"")) {
                continue 2;
            }
        }

        $orphans[] = $path;
    }

    expect($orphans)->toBe([], sprintf(
        'Config key(s) nothing reads: %s. Either wire them up or take them out; '
        .'a setting that does nothing is worse than one that is missing, because it '
        .'looks like it works.',
        implode(', ', $orphans),
    ));
});

it('documents only settings that exist', function () use ($root, $configKeys): void {
    $docs = file_get_contents($root.'/docs/configuration.md');
    $known = $configKeys();

    // Every `key.path` in a documentation table cell.
    preg_match_all('/\|\s*`([a-z_]+(?:\.[a-z_*]+)+)`\s*\|/i', (string) $docs, $matches);

    $invented = [];

    foreach (array_unique($matches[1]) as $documented) {
        if (in_array($documented, $known, true)) {
            continue;
        }

        // A parent group is a fair thing to name in prose.
        if (array_filter($known, static fn (string $k): bool => str_starts_with($k, $documented.'.'))) {
            continue;
        }

        $invented[] = $documented;
    }

    expect($invented)->toBe([], sprintf(
        'Documented setting(s) that do not exist in config/confetti.php: %s.',
        implode(', ', $invented),
    ));
});

it('documents every setting that exists', function () use ($root, $configKeys, $dataKeys, $dataSections): void {
    $docs = (string) file_get_contents($root.'/docs/configuration.md');
    $undocumented = [];

    foreach ($configKeys() as $path) {
        // Palettes, effects and defaults are described as a group rather than
        // row by row, because their keys belong to the application.
        if (in_array($path, $dataKeys, true) || isDataSection($path, $dataSections)) {
            continue;
        }

        if (! str_contains($docs, $path)) {
            $undocumented[] = $path;
        }
    }

    expect($undocumented)->toBe([], sprintf(
        'Setting(s) missing from docs/configuration.md: %s.',
        implode(', ', $undocumented),
    ));
});
