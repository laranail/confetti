<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Simtabi\Laranail\Confetti\View\ConfettiTags;

/**
 * `<x-confetti::scripts />` — the boot payload and the runtime.
 *
 * Place it once, before `</body>`. The same markup is what the auto-inject
 * middleware splices in and what the Filament plugin renders into a panel, so
 * there is exactly one definition of what confetti puts on a page.
 */
final class Scripts extends Component
{
    public function __construct(
        private readonly ConfettiTags $tags,
    ) {}

    public function render(): View
    {
        return view(ConfettiTags::VIEW, $this->tags->data());
    }
}
