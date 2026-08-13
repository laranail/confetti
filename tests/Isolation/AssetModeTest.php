<?php

declare(strict_types=1);

use Simtabi\Laranail\Confetti\Tests\Isolation\AssetsOffTestCase;

uses(AssetsOffTestCase::class);

it('does not register the asset route when delivery is not "route"', function (): void {
    // Leaving a live endpoint serving the bundle would defeat the point of
    // deliberately switching delivery somewhere else.
    $uris = collect(app('router')->getRoutes()->getRoutes())->map(fn ($route) => $route->uri());

    expect($uris)->not->toContain('vendor/confetti/{file}');

    $this->get('/vendor/confetti/confetti.js')->assertNotFound();
});
