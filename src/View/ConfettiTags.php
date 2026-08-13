<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\View;

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
    ) {}

    public function render(): string
    {
        if (! $this->config->enabled) {
            return '';
        }

        return view(self::VIEW, $this->data())->render();
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return [
            'enabled' => $this->config->enabled,
            'bootJson' => $this->boot->toJson(),
            'scriptTag' => $this->tag->render(),
        ];
    }
}
