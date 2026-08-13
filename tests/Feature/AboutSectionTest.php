<?php

declare(strict_types=1);

use Simtabi\Laranail\Confetti\Support\Assets;

it('summarises the setup in artisan about', function (): void {
    $this->artisan('about --only=confetti')
        ->expectsOutputToContain('Confetti')
        ->expectsOutputToContain('Asset delivery')
        ->expectsOutputToContain('Transport')
        ->assertSuccessful();
});

it('says plainly when the bundle has not been built', function (): void {
    // The one thing worth spotting at a glance: an unbuilt bundle means nothing
    // will ever appear, and every other setting is beside the point.
    $this->swap(
        Assets::class,
        new Assets('/nowhere'),
    );

    $this->artisan('about --only=confetti')
        ->expectsOutputToContain('NOT BUILT')
        ->assertSuccessful();
});
