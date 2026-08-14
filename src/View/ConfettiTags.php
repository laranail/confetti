<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\View;

use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\Confetti\Events\ConfettiRendered;
use Simtabi\Laranail\Confetti\Support\BootConfig;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;

/**
 * Renders the confetti markup.
 *
 * The single definition of what confetti puts on a page. Four things need it
 * (the Blade component, the auto-inject middleware, the Filament plugin and the
 * automatic panel provider) and having them share one renderer is what makes
 * "the panel and the rest of the site get identical markup" a fact rather than
 * a claim that drifts. The Filament parity test asserts exactly that.
 */
final readonly class ConfettiTags
{
    public const string VIEW = 'confetti::components.scripts';

    public function __construct(
        private ConfettiConfig $config,
        private BootConfig $boot,
        private ScriptTagBuilder $tag,
        private ?Dispatcher $events = null,
    ) {}

    public function render(string $source = 'component'): string
    {
        if (! $this->config->enabled) {
            return '';
        }

        return view(self::VIEW, $this->viewData($source))->render();
    }

    /**
     * The view data, announcing the render as it goes.
     *
     * Every path that puts confetti on a page goes through here, which is what
     * makes `ConfettiRendered` mean what the documentation says it means. The
     * Blade component used to assemble the same view from {@see data()} and so
     * announced nothing, leaving the most common integration the one case where
     * "no ConfettiRendered means the runtime never reached the page" was false.
     *
     * @return array<string, mixed>
     */
    public function viewData(string $source): array
    {
        $data = $this->data();

        if ($this->config->enabled) {
            $this->events?->dispatch(new ConfettiRendered(
                source: $source,
                hasPayload: str_contains((string) $data['bootJson'], '"payload":{'),
            ));
        }

        return $data;
    }

    /**
     * The view data alone, announcing nothing.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return [
            'enabled' => $this->config->enabled,
            'bootJson' => $this->boot->toJson(),
            'scriptTag' => $this->tag->render(),
        ];
    }
}
