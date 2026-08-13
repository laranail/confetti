<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\View\ConfettiTags;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * Appends the confetti tags to HTML responses.
 *
 * Alias: `confetti`. Registered into the configured middleware group when
 * `inject.auto` is enabled, so a plain Blade application needs no layout
 * changes at all.
 *
 * It declines a lot of responses, and each refusal is load-bearing: redirects
 * carry no body worth touching, streamed responses have no body to read
 * without buffering it, Inertia and Livewire responses are JSON envelopes that
 * would break if HTML were appended to them, and a response that already
 * carries the boot block means the Blade component is on the page, and injecting
 * again would fire everything twice.
 */
final readonly class InjectConfetti
{
    private const string MARKER = 'data-confetti-boot';

    public function __construct(
        private ConfettiConfig $config,
        private ConfettiTags $tags,
    ) {}

    public function handle(Request $request, Closure $next): BaseResponse
    {
        $response = $next($request);

        if (! $this->shouldInject($request, $response)) {
            return $response;
        }

        $content = (string) $response->getContent();

        if (str_contains($content, self::MARKER)) {
            return $response;
        }

        $response->setContent($this->splice($content, $this->tags->render()));

        // The body changed length; a stale Content-Length truncates the page.
        $response->headers->remove('Content-Length');

        return $response;
    }

    /**
     * Insert before the final `</body>`.
     *
     * The last occurrence rather than the first, and a single replacement
     * rather than a global one: `str_replace()` would also rewrite a `</body>`
     * appearing inside a JavaScript string or an escaped code sample elsewhere
     * on the page.
     */
    private function splice(string $content, string $markup): string
    {
        $position = strripos($content, '</body>');

        if ($position === false) {
            return $content.$markup;
        }

        return substr_replace($content, $markup, $position, 0);
    }

    private function shouldInject(Request $request, BaseResponse $response): bool
    {
        if (! $this->config->enabled) {
            return false;
        }

        if (! $response instanceof Response || $response->isRedirection()) {
            return false;
        }

        // Inertia and Livewire answer with JSON envelopes their clients parse
        // strictly; appending markup breaks the response rather than decorating it.
        if ($request->hasHeader('X-Inertia') || $request->hasHeader('X-Livewire')) {
            return false;
        }

        if (! str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html')) {
            return false;
        }

        return $this->pathAllowed($request);
    }

    private function pathAllowed(Request $request): bool
    {
        /** @var list<string> $only */
        $only = (array) $this->config->injectValue('only', []);
        /** @var list<string> $except */
        $except = (array) $this->config->injectValue('except', []);

        if ($except !== [] && $request->is(...$except)) {
            return false;
        }

        return $only === [] || $request->is(...$only);
    }
}
