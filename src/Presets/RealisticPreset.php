<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Presets;

use Simtabi\Laranail\Confetti\Builder\OptionStack;
use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Payload\PayloadDraft;

/**
 * Five overlapping bursts that read as one substantial explosion.
 *
 * A faithful port of the upstream "Realistic Look" recipe. The trick is that
 * all five fire at once, with no delay between them: a tight fast core, a
 * medium spread, a wide light cloud, and two slow outliers. Firing them in
 * sequence would give five small pops instead.
 *
 * The origin sits at `y: 0.7` rather than the middle of the screen, which is
 * what makes the burst look like it came from something on the page instead of
 * from nowhere.
 *
 * Ratios are of 200 particles and sum to 1.0, floored per burst: 50, 40, 70, 20
 * and 20.
 */
final readonly class RealisticPreset implements Preset
{
    private const int TOTAL_PARTICLES = 200;

    /** @var list<array{ratio: float, options: array<string, mixed>}> */
    private const array BURSTS = [
        ['ratio' => 0.25, 'options' => ['spread' => 26.0, 'startVelocity' => 55.0]],
        ['ratio' => 0.20, 'options' => ['spread' => 60.0]],
        ['ratio' => 0.35, 'options' => ['spread' => 100.0, 'decay' => 0.91, 'scalar' => 0.8]],
        ['ratio' => 0.10, 'options' => ['spread' => 120.0, 'startVelocity' => 25.0, 'decay' => 0.92, 'scalar' => 1.2]],
        ['ratio' => 0.10, 'options' => ['spread' => 120.0, 'startVelocity' => 45.0]],
    ];

    public function name(): string
    {
        return 'realistic';
    }

    public function apply(OptionStack $stack, PayloadDraft $draft): void
    {
        $stack->setPreset('origin', ['x' => 0.5, 'y' => 0.7]);

        foreach (self::BURSTS as $burst) {
            $draft->addPresetBurst([
                ...$burst['options'],
                'particleCount' => (int) floor(self::TOTAL_PARTICLES * $burst['ratio']),
            ]);
        }
    }
}
