<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Presets;

use Simtabi\Laranail\Confetti\Builder\OptionStack;
use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Enums\ConfettiShape;
use Simtabi\Laranail\Confetti\Payload\PayloadDraft;
use Simtabi\Laranail\Confetti\Payload\Shapes\BuiltInShape;

/**
 * Small purple and cyan circles — sparkles rather than paper.
 *
 * Options only. Not an upstream recipe.
 */
final readonly class MagicPreset implements Preset
{
    public function name(): string
    {
        return 'magic';
    }

    public function apply(OptionStack $stack, PayloadDraft $draft): void
    {
        $stack->setPresetMany([
            'colors' => ['#a25afd', '#ff36ff', '#26ccff', '#ffffff'],
            'shapes' => [BuiltInShape::of(ConfettiShape::Circle)],
            'scalar' => 0.8,
        ]);
    }
}
