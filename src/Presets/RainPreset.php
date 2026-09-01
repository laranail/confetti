<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Presets;

use Simtabi\Laranail\Confetti\Builder\OptionStack;
use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Enums\ConfettiPosition;
use Simtabi\Laranail\Confetti\Payload\PayloadDraft;

/**
 * A wide, slow fall from the top of the viewport.
 *
 * Halved gravity and a long tick budget keep particles on screen long enough to
 * read as rain rather than as a burst that happens to point downward. Options
 * only. Not an upstream recipe.
 */
final readonly class RainPreset implements Preset
{
    public function name(): string
    {
        return 'rain';
    }

    public function apply(OptionStack $stack, PayloadDraft $draft): void
    {
        $top = ConfettiPosition::Top;

        $stack->setPresetMany([
            'origin' => ['x' => $top->x(), 'y' => $top->y()],
            'angle' => (float) $top->angle(),
            'spread' => 180.0,
            'gravity' => 0.5,
            'drift' => 1.0,
            'ticks' => 500,
        ]);
    }
}
