<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\Confetti\Http\Controllers\AssetController;
use Simtabi\Laranail\Confetti\Support\Assets;

/**
 * The asset route, registered only when `assets.mode` is `route`.
 *
 * The filename is constrained to the two bundles the package ships, so nothing
 * else can reach the controller.
 */
$config = config('laranail.confetti.assets', []);
$prefix = trim((string) ($config['route'] ?? '/vendor/confetti'), '/');
$middleware = (array) ($config['middleware'] ?? []);
$pattern = implode('|', array_map(
    static fn (string $file): string => preg_quote($file, '/'),
    Assets::filenames(),
));

Route::get($prefix.'/{file}', AssetController::class)
    ->where('file', $pattern)
    ->middleware($middleware)
    ->name('laranail.confetti.asset');
