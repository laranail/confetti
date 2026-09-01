<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Presets;

use Simtabi\Laranail\Confetti\Builder\OptionStack;
use Simtabi\Laranail\Confetti\Contracts\ExpandableAnimation;
use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Enums\ConfettiAnimation;
use Simtabi\Laranail\Confetti\Payload\Animation;
use Simtabi\Laranail\Confetti\Payload\Burst;
use Simtabi\Laranail\Confetti\Payload\PayloadDraft;
use Simtabi\Laranail\Confetti\Support\Seed;

/**
 * Two jets firing inward from the left and right edges.
 *
 * A faithful port of the upstream "School Pride" recipe: two particles per
 * frame from each side, angled at 60 and 120 degrees so the streams arc toward
 * the middle. Two particles sounds like nothing, but at sixty frames a second
 * it is a steady jet rather than a burst.
 *
 * The emitters live in `params.sides` as a list rather than being hard-coded,
 * so a three- or four-sided variant needs configuration and not a new preset.
 *
 * Set your own team's colours with `colors()`; the red and white here are the
 * upstream default, and being on the preset layer they yield to anything the
 * caller sets, in either order.
 */
final readonly class SchoolPridePreset implements ExpandableAnimation, Preset
{
    private const int FRAME_MS = 30;

    public function __construct(
        private ?int $duration = null,
    ) {}

    public function name(): string
    {
        return 'schoolPride';
    }

    public function apply(OptionStack $stack, PayloadDraft $draft): void
    {
        $stack->setPresetMany([
            'particleCount' => 2,
            'spread' => 55.0,
            'colors' => ['#bb0000', '#ffffff'],
        ]);

        $draft->addAnimation(Animation::make(
            animation: ConfettiAnimation::SchoolPride,
            duration: $this->duration,
            params: [
                'sides' => [
                    ['angle' => 60, 'origin' => ['x' => 0.0, 'y' => 0.5]],
                    ['angle' => 120, 'origin' => ['x' => 1.0, 'y' => 0.5]],
                ],
            ],
        ));
    }

    public function expand(Animation $animation, Seed $seed): array
    {
        /** @var list<array{angle: int|float, origin: array{x: float, y: float}}> $sides */
        $sides = $animation->params['sides'] ?? [];

        $bursts = [];

        for ($elapsed = 0; $elapsed < $animation->duration; $elapsed += self::FRAME_MS) {
            foreach ($sides as $side) {
                $bursts[] = new Burst([
                    'angle' => (float) $side['angle'],
                    'origin' => $side['origin'],
                ], $elapsed);
            }
        }

        return $bursts;
    }
}
