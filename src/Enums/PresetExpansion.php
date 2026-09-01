<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Enums;

use Simtabi\Laranail\Enumerator\Attributes\Description;
use Simtabi\Laranail\Enumerator\Attributes\Help;
use Simtabi\Laranail\Enumerator\Attributes\Label;
use Simtabi\Laranail\Enumerator\Concerns\HasEnumeratorBehavior;
use Simtabi\Laranail\Enumerator\Contracts\Enumerator;

/**
 * Where a continuous effect is turned into individual bursts.
 *
 * `Client` sends a descriptor and lets the browser run the loop: a couple of
 * hundred bytes, and every visitor gets their own randomness. `Server` walks
 * the same loop in PHP and ships the resulting bursts, which is far larger on
 * the wire but produces a payload you can assert against in a test.
 */
#[Description('Whether a continuous effect is expanded into bursts in PHP or in the browser.')]
enum PresetExpansion: string implements Enumerator
{
    use HasEnumeratorBehavior;

    #[Label('Client'), Help('Send a compact descriptor; the browser runs the animation loop.')]
    case Client = 'client';

    #[Label('Server'), Help('Expand to concrete bursts in PHP. Deterministic under seed(), but a much larger payload.')]
    case Server = 'server';
}
