<?php

declare(strict_types=1);

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Support\BootConfig;
use Simtabi\Laranail\Confetti\View\ScriptTagBuilder;

/**
 * The session transport, and the replay bug it was rewritten to fix.
 */
beforeEach(function (): void {
    Route::middleware('web')->group(function (): void {
        Route::get('/fires', function (): Redirector|RedirectResponse {
            Confetti::realistic()->shoot();

            return redirect('/landing');
        });

        Route::get('/fires-twice', function (): Redirector|RedirectResponse {
            Confetti::count(10)->shoot();
            Confetti::count(20)->shoot();

            return redirect('/landing');
        });

        // Report what the request could see, rather than asserting afterwards:
        // Laravel ages the flash out at the end of the request, so by the time
        // a test reads the session the answer is always "gone".
        Route::get('/landing', fn (): string => session()->has('laranail.confetti') ? 'saw-confetti' : 'no-confetti');
        Route::get('/plain', fn (): string => session()->has('laranail.confetti') ? 'saw-confetti' : 'no-confetti');
    });
});

it('flashes the payload for the next request', function (): void {
    $this->get('/fires')->assertRedirect('/landing');

    $payload = session('laranail.confetti');

    expect($payload)->toBeArray();
    expect($payload['bursts'])->toHaveCount(5);
});

it('does not replay the payload on the request after next', function (): void {
    // The bug: the old implementation merged session()->get() into the new
    // flash. On a flashed key that read returns the *previous* request's data,
    // so re-flashing extended its life by another request — and the confetti
    // fired again on every subsequent page, indefinitely.
    $this->get('/fires')->assertRedirect('/landing');

    $this->get('/landing')->assertOk()->assertSee('saw-confetti');

    $this->get('/plain')->assertOk()->assertSee('no-confetti');
});

it('merges several shoots in one request into a single payload', function (): void {
    $this->get('/fires-twice')->assertRedirect('/landing');

    $payload = session('laranail.confetti');

    expect($payload['bursts'])->toHaveCount(2);
    expect($payload['bursts'][0]['options']['particleCount'])->toBe(10);
    expect($payload['bursts'][1]['options']['particleCount'])->toBe(20);
});

it('carries the payload into the rendered boot block', function (): void {
    Route::middleware('web')->get('/celebrate', function (): Redirector|RedirectResponse {
        Confetti::count(42)->shoot();

        return redirect('/page');
    });

    Route::middleware('web')->get('/page', fn (): Factory|View => view('confetti::components.scripts', [
        'enabled' => true,
        'bootJson' => app(BootConfig::class)->toJson(),
        'scriptTag' => app(ScriptTagBuilder::class)->render(),
    ]));

    $this->get('/celebrate');

    $this->get('/page')
        ->assertOk()
        ->assertSee('data-confetti-boot', escape: false)
        ->assertSee('"particleCount":42', escape: false);
});
