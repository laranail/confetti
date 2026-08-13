<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\Confetti\Support\Assets;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\View\ScriptTagBuilder;

/**
 * Every delivery mode is exercised, because the failure this guards against is
 * a mode that only breaks once somebody switches to it in production.
 */
function tagFor(array $assets = []): string
{
    config()->set('laranail.confetti.assets', array_merge(config('laranail.confetti.assets'), $assets));

    app()->forgetInstance(ConfettiConfig::class);

    return app(ScriptTagBuilder::class)->render();
}

it('serves the bundle from a content-hashed route by default', function (): void {
    $this->get('/vendor/confetti/confetti.js')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/javascript')
        // Symfony normalises the directive order, so match on content.
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('answers a conditional request with 304', function (): void {
    $etag = $this->get('/vendor/confetti/confetti.js')->headers->get('ETag');

    $this->withHeaders(['If-None-Match' => $etag])
        ->get('/vendor/confetti/confetti.js')
        ->assertStatus(304);
});

it('serves the module bundle too', function (): void {
    $this->get('/vendor/confetti/confetti.mjs')->assertOk();
});

it('refuses a filename outside the two it ships', function (): void {
    // The route constraint means this never reaches the controller — there is
    // no path built from user input anywhere in the asset pipeline.
    $this->get('/vendor/confetti/../../.env')->assertNotFound();
    $this->get('/vendor/confetti/anything.js')->assertNotFound();
});

it('builds asset URLs from app.url rather than the request host', function (): void {
    // A Host or X-Forwarded-Host header is attacker-controlled on many
    // deployments; reflecting one into a <script src> would let a spoofed
    // header choose where the browser fetches executable code from.
    config()->set('app.url', 'https://example.test');
    app()->forgetInstance(ConfettiConfig::class);

    Route::get('/spoofed', fn () => app(ScriptTagBuilder::class)->render());

    $this->withHeaders(['Host' => 'evil.example'])
        ->get('/spoofed')
        ->assertSee('https://example.test/vendor/confetti/confetti.js', escape: false)
        ->assertDontSee('evil.example');
});

describe('modes', function (): void {
    it('emits a content-hashed route URL', function (): void {
        expect(tagFor(['mode' => 'route']))
            ->toContain('/vendor/confetti/confetti.js?id=')
            ->toContain('type="module"')
            ->toContain('defer');
    });

    it('emits a published URL', function (): void {
        expect(tagFor(['mode' => 'published']))->toContain('/vendor/confetti/confetti.iife.js');
    });

    it('emits a CDN URL with its integrity hash', function (): void {
        expect(tagFor(['mode' => 'cdn', 'cdn_url' => 'https://cdn.test/c.js', 'cdn_integrity' => 'sha384-abc']))
            ->toContain('https://cdn.test/c.js')
            ->toContain('integrity="sha384-abc"')
            ->toContain('crossorigin="anonymous"');
    });

    it('warns rather than emitting a broken tag when the CDN URL is missing', function (): void {
        expect(tagFor(['mode' => 'cdn', 'cdn_url' => null]))->toBe('');
    });

    it('falls back to the route when a Vite entry cannot be resolved', function (): void {
        // A missing manifest entry throws in Laravel. Losing a build
        // optimisation beats a 500 on every page.
        expect(tagFor(['mode' => 'vite', 'vite_entry' => 'resources/js/nope.js']))
            ->toContain('/vendor/confetti/confetti.js');
    });

    it('emits nothing when delivery is off', function (): void {
        expect(tagFor(['mode' => 'off']))->toBe('');
    });

    it('carries a CSP nonce onto the script tag', function (): void {
        config()->set('laranail.confetti.security.csp_nonce', 'abc123');
        app()->forgetInstance(ConfettiConfig::class);

        expect(app(ScriptTagBuilder::class)->render())->toContain('nonce="abc123"');
    });
});

it('reports the built bundle', function (): void {
    $assets = app(Assets::class);

    expect($assets->exists())->toBeTrue();
    expect($assets->hash())->not->toBe('dev');
    expect($assets->size())->toBeGreaterThan(1000);
});
