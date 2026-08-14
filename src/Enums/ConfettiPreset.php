<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Enums;

use Simtabi\Laranail\Confetti\Exceptions\InvalidPreset;
use Simtabi\Laranail\Enumerator\Attributes\Description;
use Simtabi\Laranail\Enumerator\Attributes\Label;
use Simtabi\Laranail\Enumerator\Attributes\Meta;
use Simtabi\Laranail\Enumerator\Concerns\HasEnumeratorBehavior;
use Simtabi\Laranail\Enumerator\Contracts\Enumerator;

/**
 * The ready-made effects the builder can apply.
 *
 * Two pieces of metadata matter here. `kind` says whether the preset resolves
 * to concrete bursts or to an animation descriptor, which determines both the
 * payload size and whether `->expand()` has anything to do. `official` records
 * whether the effect is a faithful port of a canvas-confetti demo recipe or a
 * laranail addition, so the documentation and the doctor command can be honest
 * about provenance.
 */
#[Description('A ready-made confetti effect, resolving to either a list of bursts or an animation descriptor.')]
enum ConfettiPreset: string implements Enumerator
{
    use HasEnumeratorBehavior;

    #[Label('Stars'),        Meta(kind: 'burst', official: true)] case Stars = 'stars';
    #[Label('Success'),      Meta(kind: 'options', official: false)] case Success = 'success';
    #[Label('Magic'),        Meta(kind: 'options', official: false)] case Magic = 'magic';
    #[Label('Rain'),         Meta(kind: 'options', official: false)] case Rain = 'rain';
    #[Label('Realistic'),    Meta(kind: 'burst', official: true)] case Realistic = 'realistic';
    #[Label('Emoji'),        Meta(kind: 'burst', official: true)] case Emoji = 'emoji';
    #[Label('Fireworks'),    Meta(kind: 'animation', official: true)] case Fireworks = 'fireworks';
    #[Label('Snow'),         Meta(kind: 'animation', official: true)] case Snow = 'snow';
    #[Label('School pride'), Meta(kind: 'animation', official: true)] case SchoolPride = 'schoolPride';

    /**
     * How this preset reaches the browser, which decides what the payload holds.
     *
     * Matched rather than cast, so a typo in a `kind:` above throws here
     * instead of travelling as a string nothing downstream handles.
     *
     * @return 'animation'|'burst'|'options'
     */
    public function kind(): string
    {
        $kind = $this->meta('kind');

        return match ($kind) {
            'animation' => 'animation',
            'burst' => 'burst',
            'options' => 'options',
            default => throw InvalidPreset::unknownKind($this->value, $kind),
        };
    }

    /** Whether this is a faithful port of an upstream canvas-confetti recipe. */
    public function isOfficial(): bool
    {
        return (bool) $this->meta('official');
    }

    public function isAnimation(): bool
    {
        return $this->kind() === 'animation';
    }

    /** The matching animation case, for presets that run as a client-side loop. */
    public function animation(): ?ConfettiAnimation
    {
        return $this->isAnimation()
            ? ConfettiAnimation::from($this->value)
            : null;
    }
}
