<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\Confetti\Events\ConfettiRendered;
use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;

/**
 * Every way confetti reaches a page, and the promise each one has to keep.
 *
 * `ConfettiRendered` is documented as the answer to "did the runtime reach the
 * page at all", so a delivery path that renders without announcing itself makes
 * that diagnostic lie. The Blade component did exactly that: it assembled the
 * view from the data accessor rather than the renderer, so the most common
 * integration was the one that stayed silent.
 */
it('announces a render from the Blade component', function (): void {
    Event::fake([ConfettiRendered::class]);

    Blade::render('<x-laranail-confetti::scripts />');

    Event::assertDispatched(
        ConfettiRendered::class,
        fn (ConfettiRendered $e): bool => $e->source === 'component',
    );
});

it('announces a render from the auto-inject middleware', function (): void {
    Event::fake([ConfettiRendered::class]);

    Route::middleware(['web', 'laranail-confetti'])
        ->get('/injected', fn (): string => '<html><body>ok</body></html>');

    $this->get('/injected');

    Event::assertDispatched(
        ConfettiRendered::class,
        fn (ConfettiRendered $e): bool => $e->source === 'middleware',
    );
});

it('reports whether a payload rode along', function (): void {
    Event::fake([ConfettiRendered::class]);

    Route::middleware('web')->get('/fires', function (): Redirector|RedirectResponse {
        Confetti::stars()->shoot();

        return redirect('/lands');
    });

    Route::middleware(['web', 'laranail-confetti'])
        ->get('/lands', fn (): string => '<html><body>ok</body></html>');

    $this->get('/fires');
    $this->get('/lands');

    Event::assertDispatched(
        ConfettiRendered::class,
        fn (ConfettiRendered $e): bool => $e->hasPayload,
    );
});

it('stays silent when the package is disabled', function (): void {
    // Nothing reached the page, so nothing should claim it did.
    config()->set('laranail.confetti.enabled', false);
    app()->forgetInstance(ConfettiConfig::class);

    Event::fake([ConfettiRendered::class]);

    Blade::render('<x-laranail-confetti::scripts />');

    Event::assertNotDispatched(ConfettiRendered::class);
});

/**
 * The guard proper: no delivery path may render without announcing itself.
 *
 * Reads the source rather than a list someone has to remember to extend, so a
 * fifth entry point is covered the day it is written.
 */
it('gives every renderer a source, and routes them all through viewData', function (): void {
    $callers = [];

    foreach ((new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__.'/../../src')
    )) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $code = (string) file_get_contents($file->getPathname());

        // Anything that renders the confetti view must go through the renderer,
        // which is the only place ConfettiRendered is dispatched.
        if (preg_match_all('/->(render|viewData)\(\s*[\'"]([\w-]+)[\'"]/', $code, $m)) {
            foreach ($m[2] as $source) {
                $callers[$source] = basename($file->getPathname());
            }
        }

        // A renderer reaching for the bare data accessor is the defect this
        // guards: it produces identical markup and announces nothing.
        expect($code)->not->toMatch('/view\(\s*ConfettiTags::VIEW\s*,\s*\$this->tags->data\(\)/');
    }

    expect($callers)->toHaveKeys(['component', 'middleware', 'filament', 'filament-auto']);
});
