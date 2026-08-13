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
 * Bursts climbing the screen at intervals, thinning out as time runs down.
 *
 * A faithful port of the upstream "Fireworks" recipe: a pair of 360-degree
 * bursts every 250ms, one on each side of the screen, with the particle count
 * falling linearly so the display tapers instead of stopping dead.
 *
 * The launch height is deliberately random *above* the fold — `y` runs from
 * -0.2 to 0.8 — because particles fall, and starting them at the top of the
 * viewport would put every firework in the bottom half of the screen.
 *
 * This preset is the reason the option stack exists. Its predecessor merged its
 * settings the wrong way round, so the generic defaults overwrote them: the
 * 360-degree spread became 70 degrees and the 60-tick budget became 200. The
 * result was a narrow puff that fired for three times as long as intended and
 * looked nothing like a firework. Writing to the preset layer makes that class
 * of mistake unrepresentable.
 */
final readonly class FireworksPreset implements ExpandableAnimation, Preset
{
    /** Milliseconds between volleys, from the upstream setInterval. */
    private const int INTERVAL = 250;

    /** Particle count at full strength; scaled by the fraction of time left. */
    private const int PARTICLE_COUNT = 50;

    public function __construct(
        private ?int $duration = null,
    ) {}

    public function name(): string
    {
        return 'fireworks';
    }

    public function apply(OptionStack $stack, PayloadDraft $draft): void
    {
        $stack->setPresetMany([
            'startVelocity' => 30.0,
            'spread' => 360.0,
            'ticks' => 60,
            'zIndex' => 0,
        ]);

        $draft->addAnimation(Animation::make(
            animation: ConfettiAnimation::Fireworks,
            duration: $this->duration,
            params: [
                'interval' => self::INTERVAL,
                'particleCount' => self::PARTICLE_COUNT,
                'xRanges' => [[0.1, 0.3], [0.7, 0.9]],
                'yRange' => [-0.2, 0.8],
            ],
        ));
    }

    public function expand(Animation $animation, Seed $seed): array
    {
        $duration = $animation->duration;
        $interval = (int) ($animation->params['interval'] ?? self::INTERVAL);
        $peak = (int) ($animation->params['particleCount'] ?? self::PARTICLE_COUNT);

        /** @var list<array{float, float}> $xRanges */
        $xRanges = $animation->params['xRanges'] ?? [[0.1, 0.3], [0.7, 0.9]];
        /** @var array{float, float} $yRange */
        $yRange = $animation->params['yRange'] ?? [-0.2, 0.8];

        $bursts = [];

        for ($elapsed = 0; $elapsed < $duration; $elapsed += $interval) {
            $timeLeft = $duration - $elapsed;
            $particleCount = (int) floor($peak * ($timeLeft / $duration));

            if ($particleCount <= 0) {
                continue;
            }

            foreach ($xRanges as [$min, $max]) {
                $bursts[] = new Burst([
                    'particleCount' => $particleCount,
                    'origin' => [
                        'x' => $seed->betweenRounded($min, $max),
                        'y' => $seed->betweenRounded($yRange[0], $yRange[1]),
                    ],
                ], $elapsed);
            }
        }

        return $bursts;
    }
}
