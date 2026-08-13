<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Contracts;

use Simtabi\Laranail\Confetti\Builder\OptionStack;
use Simtabi\Laranail\Confetti\Payload\PayloadDraft;

/**
 * A named, ready-made confetti effect.
 *
 * A preset writes to the stack's `preset` layer and, if it produces more than a
 * single burst, appends to the draft. It must never write to the `user` layer —
 * that layer belongs to the caller, and the whole point of the separation is
 * that `->spread(90)->fireworks()` and `->fireworks()->spread(90)` agree.
 *
 * Register your own with `Confetti::registerPreset()`.
 */
interface Preset
{
    /** The name the builder and payloads refer to it by. */
    public function name(): string;

    /**
     * Apply the effect.
     *
     * Presets that resolve to a single burst need only set options on the
     * stack; the builder commits the burst afterwards. Presets that produce
     * several bursts, or an animation, append them to the draft.
     */
    public function apply(OptionStack $stack, PayloadDraft $draft): void;
}
