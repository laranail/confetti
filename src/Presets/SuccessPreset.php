<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Presets;

use Simtabi\Laranail\Confetti\Builder\OptionStack;
use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Payload\PayloadDraft;

/**
 * A green palette for the "that worked" moment.
 *
 * Options only — it changes colours and nothing else, so combine it with a
 * position or another effect. Not an upstream recipe.
 */
final readonly class SuccessPreset implements Preset
{
    public function name(): string
    {
        return 'success';
    }

    public function apply(OptionStack $stack, PayloadDraft $draft): void
    {
        $stack->setPreset('colors', ['#00ff00', '#32cd32', '#00ef10', '#adff2f', '#7cfc00']);
    }
}
