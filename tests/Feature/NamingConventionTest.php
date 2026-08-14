<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

/**
 * Every public name this package claims is prefixed with the org and the slug.
 *
 * Laravel's registries for view namespaces, Blade component prefixes and route
 * middleware aliases are all flat maps keyed by that name. Two packages
 * claiming the same key do not conflict loudly; the second silently replaces
 * the first, and the failure surfaces later as a missing view or the wrong
 * middleware. A name as generic as "confetti" is a plausible collision with
 * another package or with the application's own.
 *
 * Artisan commands take `laranail::confetti.*` instead, because Symfony
 * resolves an exact name before splitting on `:`. Nothing else here can use
 * `::`: Laravel splits middleware aliases on `:` to take parameters, and in a
 * Blade tag `::` already separates the prefix from the component.
 */
const PREFIX = 'laranail-confetti';

it('registers its views under the org-scoped namespace', function (): void {
    $hints = View::getFinder()->getHints();

    expect($hints)->toHaveKey(PREFIX);
    expect($hints)->not->toHaveKey('confetti');
});

it('registers its Blade components under the org-scoped prefix', function (): void {
    $namespaces = new ReflectionProperty(Blade::getFacadeRoot(), 'classComponentNamespaces')
        ->getValue(Blade::getFacadeRoot());

    expect($namespaces)->toHaveKey(PREFIX);
    expect($namespaces[PREFIX])->toBe('Simtabi\Laranail\Confetti\View\Components');
    expect($namespaces)->not->toHaveKey('confetti');
});

it('registers its route middleware under the org-scoped alias', function (): void {
    // Aliases live on the router, not the HTTP kernel.
    $aliases = app('router')->getMiddleware();

    expect($aliases)->toHaveKey(PREFIX);
    expect($aliases)->not->toHaveKey('confetti');
});

it('names its Artisan commands with the double-colon form', function (): void {
    $names = array_keys(app('Illuminate\Contracts\Console\Kernel')->all());

    $ours = array_values(array_filter(
        $names,
        static fn (string $name): bool => str_contains($name, 'confetti'),
    ));

    expect($ours)->not->toBeEmpty();

    foreach ($ours as $name) {
        expect($name)->toStartWith('laranail::confetti.');
    }
});

it('keeps the component tag resolvable under the new prefix', function (): void {
    // The convention is only worth anything if the documented tag still works.
    expect(Blade::render('<x-laranail-confetti::scripts />'))
        ->toContain('data-confetti-boot');
});

it('leaves no bare-prefixed names in the source', function (): void {
    $offenders = [];

    foreach ([
        "/->hasViews\('(?!laranail-)/",
        "/aliasMiddleware\('(?!laranail-)/",
        "/hasBladeComponentNamespace\([^)]*'(?!laranail-)[\w-]+'\s*,?\s*\)/s",
    ] as $pattern) {
        foreach (glob(__DIR__.'/../../src/Providers/*.php') ?: [] as $file) {
            if (preg_match($pattern, (string) file_get_contents($file))) {
                $offenders[] = basename($file).' matched '.$pattern;
            }
        }
    }

    expect($offenders)->toBe([]);
});
