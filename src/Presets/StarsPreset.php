<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Presets;

use Simtabi\Laranail\Confetti\Builder\OptionStack;
use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Enums\ConfettiShape;
use Simtabi\Laranail\Confetti\Payload\PayloadDraft;
use Simtabi\Laranail\Confetti\Payload\Shapes\BuiltInShape;

/**
 * Gold stars bursting outward and hanging in the air.
 *
 * A faithful port of the upstream "Stars" recipe. Zero gravity is what makes it
 * work: the particles stop where they land and fade over the 50-tick budget
 * instead of falling, and each volley pairs 40 stars with 10 small circles so
 * the effect has some grit in it. Fired three times, 100ms apart.
 *
 * This is a whole effect, not a colour scheme. If all you want is the gold
 * palette, use `palette('gold')->shapes('star')->scalar(1.2)`.
 */
final readonly class StarsPreset implements Preset
{
    /** Delays for the three volleys, in milliseconds. */
    private const array VOLLEYS = [0, 100, 200];

    public function name(): string
    {
        return 'stars';
    }

    public function apply(OptionStack $stack, PayloadDraft $draft): void
    {
        $stack->setPresetMany([
            'spread' => 360.0,
            'ticks' => 50,
            'gravity' => 0.0,
            'decay' => 0.94,
            'startVelocity' => 30.0,
            'colors' => ['#ffe400', '#ffbd00', '#e89400', '#ffca6c', '#fdffb8'],
        ]);

        foreach (self::VOLLEYS as $delay) {
            $draft->addPresetBurst([
                'particleCount' => 40,
                'scalar' => 1.2,
                'shapes' => [BuiltInShape::of(ConfettiShape::Star)],
            ], $delay);

            $draft->addPresetBurst([
                'particleCount' => 10,
                'scalar' => 0.75,
                'shapes' => [BuiltInShape::of(ConfettiShape::Circle)],
            ], $delay);
        }
    }
}
