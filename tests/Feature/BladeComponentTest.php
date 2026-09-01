<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\View\ConfettiTags;

/**
 * `<x-laranail-confetti::scripts />` is the integration point the README tells people to
 * place, and it was the one path nothing rendered. The install command asserted
 * it prints the tag; no test proved the tag resolves.
 */
it('resolves the component from its registered namespace', function (): void {
    $html = Blade::render('<x-laranail-confetti::scripts />');

    expect($html)->toContain('data-confetti-boot');
    expect($html)->toContain('<script type="module"');
});

it('emits exactly two elements and no inline javascript', function (): void {
    $html = Blade::render('<x-laranail-confetti::scripts />');

    expect(substr_count($html, '<script'))->toBe(2);

    // The boot node is data, not code, which is what keeps the package off
    // unsafe-inline. An inline handler or a bare <script> with a body would
    // silently reintroduce that requirement.
    expect($html)->not->toMatch('/<script(?![^>]*\b(?:type="application\/json"|src=))/');
});

it('renders byte-identical markup to every other entry point', function (): void {
    // Four things put confetti on a page: this component, the auto-inject
    // middleware, the Filament plugin and the panel provider. They share one
    // renderer so that "the panel and the rest of the site agree" is a fact.
    $component = Blade::render('<x-laranail-confetti::scripts />');
    $renderer = app(ConfettiTags::class)->render();

    expect(trim($component))->toBe(trim($renderer));
});

it('carries a payload fired earlier in the same request', function (): void {
    // Needs a real request: the payload rides in the session, and a bare
    // Blade::render() has none, so the boot node correctly says payload: null.
    Route::middleware('web')->get('/same-request', function (): string {
        Confetti::stars()->shoot();

        return Blade::render('<x-laranail-confetti::scripts />');
    });

    $html = $this->get('/same-request')->getContent();

    expect($html)->toContain('"payload":{');
    expect($html)->toContain('"bursts"');
});

it('carries a payload flashed by the previous request', function (): void {
    Route::middleware('web')->get('/fires', function (): Redirector|RedirectResponse {
        Confetti::realistic()->shoot();

        return redirect('/lands');
    });

    Route::middleware('web')->get('/lands', fn (): string => Blade::render('<x-laranail-confetti::scripts />'));

    $this->get('/fires');

    expect($this->get('/lands')->getContent())->toContain('"payload":{');
});

it('renders nothing at all when the package is disabled', function (): void {
    config()->set('laranail.confetti.enabled', false);
    app()->forgetInstance(ConfettiConfig::class);

    expect(trim(app(ConfettiTags::class)->render()))->toBe('');
});
