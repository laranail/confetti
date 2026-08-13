<?php

declare(strict_types=1);

/**
 * The module boundaries, enforced.
 *
 * Each of these encodes a mistake that would be easy to reintroduce and hard to
 * notice — a `session()` call somewhere new breaks queue workers, an
 * `mt_rand()` makes server-side expansion non-reproducible, a compiled
 * `Filament\` reference makes the package fail to autoload on a plain Laravel
 * app.
 *
 * The vendor-namespace checks run over PHP's own tokens rather than the raw
 * text, which matters here: a class name written as a *string* is not a
 * boundary violation, it is the approved pattern. `class_exists('Inertia\Inertia')`
 * is how an optional integration is detected without depending on it, and a
 * text search cannot tell that apart from an import that would fatal.
 *
 * Every assertion names the offending files, because "a boundary was crossed"
 * is not much help on its own.
 */
$root = dirname(__DIR__, 2);

/** @return array<string, string> path => contents */
$sources = static function () use ($root): array {
    $files = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src'));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[$file->getPathname()] = (string) file_get_contents($file->getPathname());
        }
    }

    return $files;
};

/**
 * The code with comments, docblocks and string literals removed.
 *
 * What is left is only what PHP actually compiles.
 */
$code = static function (string $contents): string {
    $kept = '';

    foreach (token_get_all($contents) as $token) {
        if (is_array($token)) {
            if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING], true)) {
                continue;
            }

            $kept .= $token[1];

            continue;
        }

        $kept .= $token;
    }

    return $kept;
};

/**
 * Files that reference a vendor root namespace as a real, compiled class name.
 *
 * @return list<string>
 */
$referencing = static function (string $vendor, string ...$allowed) use ($root, $sources): array {
    $found = [];

    foreach ($sources() as $path => $contents) {
        foreach ($allowed as $exempt) {
            if (str_contains($path, $exempt)) {
                continue 2;
            }
        }

        foreach (token_get_all($contents) as $token) {
            if (! is_array($token)) {
                continue;
            }

            if (! in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                continue;
            }

            // Only the first segment matters: our own
            // Confetti\Integrations\Filament\… is not a Filament class.
            if (explode('\\', ltrim($token[1], '\\'))[0] === $vendor) {
                $found[] = str_replace($root.'/', '', $path);

                break;
            }
        }
    }

    sort($found);

    return array_values(array_unique($found));
};

/** @return list<string> */
$calling = static function (string $pattern, string ...$allowed) use ($root, $sources, $code): array {
    $found = [];

    foreach ($sources() as $path => $contents) {
        foreach ($allowed as $exempt) {
            if (str_contains($path, $exempt)) {
                continue 2;
            }
        }

        if (preg_match($pattern, $code($contents)) === 1) {
            $found[] = str_replace($root.'/', '', $path);
        }
    }

    sort($found);

    return $found;
};

/** @return list<string> */
$missing = static function (string $pattern, string $within, string ...$exempt) use ($root, $sources): array {
    $found = [];

    foreach ($sources() as $path => $contents) {
        if ($within !== '' && ! str_contains($path, $within)) {
            continue;
        }

        foreach ($exempt as $skip) {
            if (str_contains($path, $skip)) {
                continue 2;
            }
        }

        if (preg_match($pattern, $contents) !== 1) {
            $found[] = str_replace($root.'/', '', $path);
        }
    }

    sort($found);

    return $found;
};

it('compiles no Filament reference outside its integration directory', function () use ($referencing): void {
    // A Filament class named anywhere else is resolved on autoload, and fatal
    // on an application that does not have Filament installed. Referring to one
    // by string, as the doctor checks do, is fine and is the point.
    expect($referencing('Filament', '/src/Integrations/Filament/'))->toBe([]);
});

it('compiles no Livewire reference at all', function () use ($referencing): void {
    // Not even in the Livewire transport: it reaches the manager entirely
    // through string class names and is_callable, so the package neither
    // requires Livewire nor breaks when its internals move between majors.
    expect($referencing('Livewire'))->toBe([]);
});

it('compiles no Inertia reference at all', function () use ($referencing): void {
    expect($referencing('Inertia'))->toBe([]);
});

it('calls the session helper in exactly one place', function () use ($calling): void {
    // Reaching for session() outside the session transport is what made the
    // previous implementation throw inside console commands and queue workers.
    expect($calling('/(?<![\w>$\\\\])session\s*\(/', '/src/Transport/SessionTransport.php'))->toBe([]);
});

it('draws random numbers only from the seeded generator', function () use ($calling): void {
    // Server-side expansion exists in order to be reproducible. A bare
    // mt_rand() would break that quietly, and only in a test comparing two runs.
    expect($calling('/\b(mt_rand|random_int|shuffle|array_rand)\s*\(/', '/src/Support/Seed.php'))->toBe([]);
});

it('encodes for the page through one helper', function () use ($calling): void {
    // The escaping flags on Support\Json are what stop a </script> sequence in
    // user-supplied text breaking out of the boot block. A raw json_encode
    // elsewhere would bypass them.
    expect($calling('/json_encode\s*\(/', '/src/Support/Json.php'))->toBe([]);
});

it('makes every payload object immutable', function () use ($missing): void {
    // PendingBursts and PayloadDraft are the two deliberate exceptions: they
    // are the mutable accumulators everything else is frozen out of.
    expect($missing('/final readonly class/', '/src/Payload/', 'PendingBursts.php', 'PayloadDraft.php'))->toBe([]);
});

it('gives every exception the marker interface', function () use ($missing): void {
    // It is what makes "catch any confetti failure" a single clause.
    expect($missing('/implements ConfettiException/', '/src/Exceptions/', 'ConfettiException.php'))->toBe([]);
});

it('declares strict types everywhere', function () use ($missing): void {
    expect($missing('/declare\(strict_types=1\)/', ''))->toBe([]);
});
