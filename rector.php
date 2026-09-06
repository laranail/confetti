<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;

return RectorConfig::configure()
    ->withSkip([
        // Load-bearing, not an oversight. The optional integrations name
        // Livewire, Filament and Inertia classes as *strings* precisely so this
        // package neither requires them nor fails to autoload without them.
        // Turning those into ::class constants would resolve the class at
        // compile time and fatal on a plain Laravel application: the exact
        // failure tests/Arch/BoundaryTest.php exists to prevent.
        StringClassNameToClassConstantRector::class,

        // Same reason, and it fails louder. Rewriting
        // `[self::MANAGER, 'isLivewireRequest']` to `self::isLivewireRequest(...)`
        // rebinds the call to *this* class, which happens to have a private
        // method of that name, turning a duck-typed probe into infinite
        // recursion that the type checker cannot see.
        ArrayToFirstClassCallableRector::class,

        // The (bool) cast on a duck-typed call is not redundant. Rector infers
        // the return type from the Livewire currently installed; the point of
        // calling it dynamically is that we do not assume that version is the
        // one running.
        RecastingRemovalRector::class => [
            __DIR__ . '/src/Transport/LivewireTransport.php',
        ],
    ])
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // The floor is ^8.4.1, so the 8.4 idioms are safe to apply. No downgrade
    // set is needed here, unlike packages that still support 8.3.
    ->withSets([LevelSetList::UP_TO_PHP_84])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);
