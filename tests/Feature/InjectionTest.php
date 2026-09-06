<?php

declare(strict_types=1);

use Illuminate\Routing\Redirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Support\BootConfig;
use Simtabi\Laranail\Confetti\View\ScriptTagBuilder;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;

beforeEach(function (): void {
    Route::middleware(['web', 'laranail-confetti'])->group(function (): void {
        Route::get('/page', fn (): string => '<html><body><p>hello</p></body></html>');
        Route::get('/json', fn () => response()->json(['ok' => true]));
        Route::get('/away', fn (): Redirector|RedirectResponse => redirect('/page'));
        Route::get('/no-body', fn (): string => '<p>fragment</p>');

        // A page that already carries the component.
        Route::get('/explicit', fn (): string => '<html><body>'
            . view('laranail-confetti::components.scripts', [
                'enabled'   => true,
                'bootJson'  => app(BootConfig::class)->toJson(),
                'scriptTag' => app(ScriptTagBuilder::class)->render(),
            ])->render()
            . '</body></html>');

        // A page whose own content mentions the closing tag.
        Route::get('/mentions-body', fn (): string => '<html><body><code>&lt;/body&gt;</code>'
            . "<script>var s = '</body>'</script></body></html>");
    });
});

it('appends the confetti tags to an HTML response', function (): void {
    $html = $this->get('/page')->assertOk()->getContent();

    expect($html)->toContain('data-confetti-boot');
    expect($html)->toContain('<script type="module"');

    // Placed inside the body, after the page's own content.
    expect(strpos($html, 'data-confetti-boot'))
        ->toBeGreaterThan(strpos($html, '<p>hello</p>'))
        ->toBeLessThan(strpos($html, '</body>'));
});

it('inserts before the last closing body tag, not the first mention of one', function (): void {
    // str_replace() would rewrite the occurrence inside the inline script too,
    // corrupting the page. The splice targets the final one only.
    $html = $this->get('/mentions-body')->assertOk()->getContent();

    expect(substr_count($html, 'data-confetti-boot'))->toBe(1);
    expect($html)->toContain("var s = '</body>'");
    expect(strpos($html, 'data-confetti-boot'))->toBeGreaterThan(strrpos($html, "var s = '</body>'"));
});

it('does not inject twice when the component is already on the page', function (): void {
    $html = $this->get('/explicit')->assertOk()->getContent();

    expect(substr_count($html, 'data-confetti-boot'))->toBe(1);
});

it('leaves JSON responses alone', function (): void {
    $this->get('/json')->assertOk()->assertJson(['ok' => true]);

    expect($this->get('/json')->getContent())->not->toContain('data-confetti-boot');
});

it('leaves redirects alone', function (): void {
    expect($this->get('/away')->getContent())->not->toContain('data-confetti-boot');
});

it('appends to a fragment that has no body tag at all', function (): void {
    expect($this->get('/no-body')->getContent())->toContain('data-confetti-boot');
});

it('recomputes the content length so the page is not truncated', function (): void {
    $response = $this->get('/page');

    expect($response->headers->has('Content-Length'))->toBeFalse();
});

it('skips a path listed in the exception list', function (): void {
    config()->set('laranail.confetti.inject.except', ['page']);
    app()->forgetInstance(ConfettiConfig::class);

    expect($this->get('/page')->getContent())->not->toContain('data-confetti-boot');
});

it('carries a flashed payload through to the injected block', function (): void {
    Route::middleware(['web', 'laranail-confetti'])->get('/fire', function (): Redirector|RedirectResponse {
        Confetti::count(99)->shoot();

        return redirect('/page');
    });

    $this->get('/fire');

    $this->get('/page')->assertSee('"particleCount":99', escape: false);
});
