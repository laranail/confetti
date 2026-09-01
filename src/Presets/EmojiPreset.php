<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Presets;

use Simtabi\Laranail\Confetti\Builder\OptionStack;
use Simtabi\Laranail\Confetti\Contracts\Preset;
use Simtabi\Laranail\Confetti\Enums\ConfettiShape;
use Simtabi\Laranail\Confetti\Payload\PayloadDraft;
use Simtabi\Laranail\Confetti\Payload\Shapes\BuiltInShape;
use Simtabi\Laranail\Confetti\Payload\Shapes\TextShape;

/**
 * Emoji hanging in the air and fading, rather than falling.
 *
 * A faithful port of the upstream "Emoji" recipe. Zero gravity with a fast
 * decay is what suspends them; the 60-tick budget is what makes them disappear
 * before the joke wears out.
 *
 * Each volley is three bursts: thirty tumbling glyphs, five flat ones (which
 * read as facing the viewer), and fifteen half-size circles that stop the
 * effect looking like clip-art. Fired three times, 100ms apart.
 *
 * The text shape is created without an explicit scalar so it inherits the
 * burst's, because canvas-confetti rasterises the glyph once at `10 * scalar` pixels
 * and then scales that bitmap when drawing, so the two values have to agree or
 * the emoji renders blurred and wrong-sized.
 */
final readonly class EmojiPreset implements Preset
{
    private const array VOLLEYS = [0, 100, 200];

    private const float SCALAR = 2.0;

    public function __construct(
        private string $text = '🦄',
    ) {}

    public function name(): string
    {
        return 'emoji';
    }

    public function apply(OptionStack $stack, PayloadDraft $draft): void
    {
        $stack->setPresetMany([
            'spread' => 360.0,
            'ticks' => 60,
            'gravity' => 0.0,
            'decay' => 0.96,
            'startVelocity' => 20.0,
            'scalar' => self::SCALAR,
            'shapes' => [new TextShape($this->text)],
        ]);

        $scalar = (float) ($stack->get('scalar') ?? self::SCALAR);

        foreach (self::VOLLEYS as $delay) {
            $draft->addPresetBurst(['particleCount' => 30], $delay);
            $draft->addPresetBurst(['particleCount' => 5, 'flat' => true], $delay);
            $draft->addPresetBurst([
                'particleCount' => 15,
                'scalar' => $scalar / 2,
                'shapes' => [BuiltInShape::of(ConfettiShape::Circle)],
            ], $delay);
        }
    }
}
