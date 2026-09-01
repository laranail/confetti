<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Simtabi\Laranail\Confetti\View\ConfettiTags;

/**
 * `<x-laranail-confetti::scripts />` renders the boot payload and the runtime.
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
        // viewData(), not data(): going through the renderer is what dispatches
        // ConfettiRendered, and this is the path most applications use.
        return view(ConfettiTags::VIEW, $this->tags->viewData('component'));
    }
}
