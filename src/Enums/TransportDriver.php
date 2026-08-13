<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Enums;

use Simtabi\Laranail\Enumerator\Attributes\Description;
use Simtabi\Laranail\Enumerator\Attributes\Help;
use Simtabi\Laranail\Enumerator\Attributes\Label;
use Simtabi\Laranail\Enumerator\Concerns\HasEnumeratorBehavior;
use Simtabi\Laranail\Enumerator\Contracts\Enumerator;

/**
 * How a payload gets from PHP to the browser.
 *
 * `Auto` is the default and resolves per request; the rest force a specific
 * driver, which is mostly useful in tests or when a request looks like one
 * thing and should be treated as another.
 */
#[Description('The mechanism carrying a confetti payload from the server to the browser.')]
enum TransportDriver: string implements Enumerator
{
    use HasEnumeratorBehavior;

    #[Label('Automatic'), Help('Pick a driver per request: Livewire, then Inertia, then session, then null.')]
    case Auto = 'auto';

    #[Label('Session'), Help('Flash the payload so it renders on the next response — the classic redirect flow.')]
    case Session = 'session';

    #[Label('Livewire'), Help('Dispatch a browser event from the current component, with no page load.')]
    case Livewire = 'livewire';

    #[Label('Inertia'), Help('Share the payload as a page prop on the current response.')]
    case Inertia = 'inertia';

    #[Label('Discard'), Help('Drop the payload. Resolved outside HTTP — console commands and queued jobs.')]
    case Null = 'null';

    #[Label('Record'), Help('Record payloads in memory instead of sending them. Backs Confetti::fake().')]
    case Array = 'array';
}
