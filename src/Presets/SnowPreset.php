<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Presets;

use Simtabi\Laranail\Confetti\Builder\OptionStack;
use Simtabi\Laranail\Confetti\Contracts\ExpandableAnimation;
use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Enums\ConfettiAnimation;
use Simtabi\Laranail\Confetti\Enums\ConfettiShape;
use Simtabi\Laranail\Confetti\Payload\Animation;
use Simtabi\Laranail\Confetti\Payload\Burst;
use Simtabi\Laranail\Confetti\Payload\PayloadDraft;
use Simtabi\Laranail\Confetti\Payload\Shapes\BuiltInShape;
use Simtabi\Laranail\Confetti\Support\Seed;

/**
 * Single flakes drifting down, one per frame.
 *
 * A faithful port of the upstream "Snow" recipe. One particle per animation
 * frame, launched with no velocity so gravity alone carries it, each with its
 * own weight, size and sideways drift.
 *
 * The `skew` term is what stops it looking mechanical: it starts at 1 and
 * creeps down to 0.8, so the band flakes are born in narrows over time and the
 * snowfall appears to settle in rather than switching on. The tick budget
 * shrinks alongside the remaining duration, so the last flakes vanish rather
 * than being cut off mid-fall.
 *
 * At the default duration this is roughly five hundred separate `confetti()`
 * calls — around 150KB if serialised, with the randomness fixed at render time
 * so every visitor watches an identical snowfall. As a descriptor it is about
 * 250 bytes and each browser rolls its own.
 */
final readonly class SnowPreset implements ExpandableAnimation, Preset
{
    /** Frame interval assumed when walking the loop in PHP, matching 60fps. */
    private const int FRAME_MS = 30;

    public function __construct(
        private ?int $duration = null,
    ) {}

    public function name(): string
    {
        return 'snow';
    }

    public function apply(OptionStack $stack, PayloadDraft $draft): void
    {
        $stack->setPresetMany([
            'particleCount' => 1,
            'startVelocity' => 0.0,
            'colors' => ['#ffffff'],
            'shapes' => [BuiltInShape::of(ConfettiShape::Circle)],
        ]);

        $draft->addAnimation(Animation::make(
            animation: ConfettiAnimation::Snow,
            duration: $this->duration,
            params: [
                'ticksMin' => 200,
                'ticksMax' => 500,
                'skewFrom' => 1.0,
                'skewTo' => 0.8,
                'skewStep' => 0.001,
                'gravity' => [0.4, 0.6],
                'scalar' => [0.4, 1.0],
                'drift' => [-0.4, 0.4],
            ],
        ));
    }

    public function expand(Animation $animation, Seed $seed): array
    {
        $duration = $animation->duration;
        $params = $animation->params;

        $ticksMin = (int) ($params['ticksMin'] ?? 200);
        $ticksMax = (int) ($params['ticksMax'] ?? 500);
        $skew = (float) ($params['skewFrom'] ?? 1.0);
        $skewTo = (float) ($params['skewTo'] ?? 0.8);
        $skewStep = (float) ($params['skewStep'] ?? 0.001);

        /** @var array{float, float} $gravity */
        $gravity = $params['gravity'] ?? [0.4, 0.6];
        /** @var array{float, float} $scalar */
        $scalar = $params['scalar'] ?? [0.4, 1.0];
        /** @var array{float, float} $drift */
        $drift = $params['drift'] ?? [-0.4, 0.4];

        $bursts = [];

        for ($elapsed = 0; $elapsed < $duration; $elapsed += self::FRAME_MS) {
            $timeLeft = $duration - $elapsed;
            $skew = max($skewTo, $skew - $skewStep);

            $bursts[] = new Burst([
                'ticks' => max($ticksMin, (int) ($ticksMax * ($timeLeft / $duration))),
                'origin' => [
                    'x' => $seed->betweenRounded(0.0, 1.0),
                    'y' => round($seed->float() * $skew - 0.2, 4),
                ],
                'gravity' => $seed->betweenRounded($gravity[0], $gravity[1], 3),
                'scalar' => $seed->betweenRounded($scalar[0], $scalar[1], 3),
                'drift' => $seed->betweenRounded($drift[0], $drift[1], 3),
            ], $elapsed);
        }

        return $bursts;
    }
}
