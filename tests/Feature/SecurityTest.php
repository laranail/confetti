<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\Confetti\Builder\ConfettiBuilder;
use Simtabi\Laranail\Confetti\Exceptions\InvalidEffect;
use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\Support\EffectRegistry;
use Simtabi\Laranail\Confetti\Support\Json;
use Simtabi\Laranail\Confetti\View\ScriptTagBuilder;

/**
 * The security properties this package claims, asserted rather than assumed.
 */
describe('effect definitions cannot reach control flow', function (): void {
    it('refuses to dispatch, redirect or expand from a definition', function (): void {
        // An effect says what confetti looks like. Deciding when it fires and
        // which transport carries it belongs at the call site, where it is
        // visible. method_exists() alone allowed all of these, which only
        // stayed harmless while every definition came from a developer.
        foreach (['shoot', 'via', 'expand', 'seed', 'then', 'reset'] as $method) {
            $registry = new EffectRegistry(['probe' => [$method => true]]);

            expect(fn (): ConfettiBuilder => $registry->apply('probe', Confetti::make()))
                ->toThrow(InvalidEffect::class, 'not something an effect may do');
        }
    });

    it('tells a typo apart from a forbidden method', function (): void {
        $typo = new EffectRegistry(['probe' => ['particleCount' => 10]]);
        $forbidden = new EffectRegistry(['probe' => ['shoot' => true]]);

        expect(fn (): ConfettiBuilder => $typo->apply('probe', Confetti::make()))
            ->toThrow(InvalidEffect::class, 'not a builder method');

        expect(fn (): ConfettiBuilder => $forbidden->apply('probe', Confetti::make()))
            ->toThrow(InvalidEffect::class, 'belongs at the call site');
    });

    it('still allows everything that configures an effect', function (): void {
        $registry = new EffectRegistry(['probe' => [
            'count' => 30,
            'palette' => 'gold',
            'position' => 'top-left',
            'preset' => 'stars',
            'reducedMotion' => 'skip',
            'option' => ['custom', 'value'],
        ]]);

        expect(fn (): ConfettiBuilder => $registry->apply('probe', Confetti::make()))->not->toThrow(InvalidEffect::class);
    });
});

describe('the boot payload cannot break out of its script block', function (): void {
    it('escapes a closing script tag in user-supplied text', function (): void {
        $payload = Confetti::shapeFromText('</script><img src=x onerror=alert(1)>')->toArray();

        $encoded = Json::encode($payload);

        expect(str_contains($encoded, '</script>'))->toBeFalse();
        expect(str_contains($encoded, '<img'))->toBeFalse();
        expect(str_contains($encoded, '<'))->toBeFalse();
        expect(str_contains($encoded, '>'))->toBeFalse();
    });

    it('escapes quotes and ampersands too', function (): void {
        $encoded = Json::encode(['t' => '"\'&<>']);

        foreach (['"', "'", '&', '<', '>'] as $raw) {
            // The structural quotes of the JSON itself are the only literals.
            expect(substr_count($encoded, $raw))->toBeLessThanOrEqual(substr_count('{"t":""}', $raw));
        }
    });

    it('survives the round trip, so escaping costs nothing', function (): void {
        $text = '</script>🦄&"\'<>';

        expect(json_decode(Json::encode(['t' => $text]), true)['t'])->toBe($text);
    });

    it('renders the payload inert in a real response', function (): void {
        Route::middleware('web')->get('/xss', function (): Redirector|RedirectResponse {
            Confetti::shapeFromText('</script><script>alert(1)</script>')->shoot();

            return redirect('/landed');
        });

        Route::middleware(['web', 'laranail-confetti'])->get('/landed', fn (): string => '<html><body>ok</body></html>');

        $this->get('/xss');
        $html = $this->get('/landed')->getContent();

        // One opening and one closing tag for each of the two script elements
        // the component emits, and no third pair smuggled in through the text.
        expect(substr_count($html, '</script>'))->toBe(2);
        expect($html)->not->toContain('alert(1)</script>');
    });
});

describe('the asset route', function (): void {
    it('serves only the two bundles it ships', function (): void {
        $this->get('/vendor/confetti/confetti.js')->assertOk();
        $this->get('/vendor/confetti/confetti.mjs')->assertOk();

        // The route constraint is a fixed alternation, so nothing else reaches
        // the controller and there is no path built from the request at all.
        foreach ([
            '/vendor/confetti/../../.env',
            '/vendor/confetti/..%2f..%2f.env',
            '/vendor/confetti/composer.json',
            '/vendor/confetti/confetti.js.map',
            '/vendor/confetti/',
        ] as $attempt) {
            $this->get($attempt)->assertNotFound();
        }
    });

    it('refuses to be told where its own file lives', function (): void {
        // Even reaching the controller directly with an arbitrary name is
        // matched against the map rather than joined onto a path.
        $response = $this->get('/vendor/confetti/confetti.js?file=../../.env');

        expect($response->getContent())->toContain('LaranailConfetti');
    });
});

describe('asset URLs ignore the request', function (): void {
    it('builds from app.url rather than a spoofed Host header', function (): void {
        config()->set('app.url', 'https://real.example');
        app()->forgetInstance(ConfettiConfig::class);

        Route::get('/tag', fn () => app(ScriptTagBuilder::class)->render());

        foreach (['evil.example', 'localhost:1337'] as $spoof) {
            $this->withHeaders(['Host' => $spoof, 'X-Forwarded-Host' => $spoof])
                ->get('/tag')
                ->assertSee('https://real.example/vendor/confetti/', escape: false)
                ->assertDontSee($spoof);
        }
    });

    it('escapes a CDN URL and nonce into the tag', function (): void {
        config()->set('laranail.confetti.assets', array_merge(
            config('laranail.confetti.assets'),
            ['mode' => 'cdn', 'cdn_url' => 'https://cdn.test/c.js" onload="alert(1)'],
        ));
        config()->set('laranail.confetti.security.csp_nonce', 'abc" onload="alert(1)');
        app()->forgetInstance(ConfettiConfig::class);

        $tag = app(ScriptTagBuilder::class)->render();

        expect($tag)->not->toContain('onload="alert(1)"');
        expect(substr_count($tag, '<script'))->toBe(1);
    });
});
