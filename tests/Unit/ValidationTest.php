<?php

declare(strict_types=1);

use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Enums\ConfettiShape;
use Simtabi\Laranail\Confetti\Exceptions\InvalidColor;
use Simtabi\Laranail\Confetti\Exceptions\InvalidShape;
use Simtabi\Laranail\Confetti\Exceptions\InvalidOption;
use Simtabi\Laranail\Confetti\Validation\ColorValidator;
use Simtabi\Laranail\Confetti\Exceptions\ConfettiException;

describe('colours', function (): void {
    it('rejects anything that is not hex', function (): void {
        // canvas-confetti parses a colour by deleting non-hex characters and
        // reading what is left, so 'red' becomes 'ed' and paints something
        // arbitrary. It never errors, which is exactly the problem.
        expect(fn () => Confetti::colors('red'))
            ->toThrow(InvalidColor::class, 'must be hex strings');

        expect(fn () => Confetti::colors('rgb(255, 0, 0)'))->toThrow(InvalidColor::class);
    });

    it('normalises shorthand and casing so payloads compare cleanly', function (): void {
        expect(ColorValidator::normalise('#FFF'))->toBe('#ffffff');
        expect(ColorValidator::normalise('26CCFF'))->toBe('#26ccff');
        expect(ColorValidator::normalise('  #a25afd  '))->toBe('#a25afd');
    });

    it('accepts hex with or without the hash, as canvas-confetti does', function (): void {
        $payload = Confetti::colors('FFE400', '#fdffb8')->toArray();

        expect(burstOption($payload, 'colors'))->toBe(['#ffe400', '#fdffb8']);
    });

    it('refuses an empty palette', function (): void {
        expect(fn () => Confetti::colors([]))->toThrow(InvalidColor::class, 'at least one colour');
    });
});

describe('shapes', function (): void {
    it('rejects a name canvas-confetti would silently draw as a square', function (): void {
        expect(fn () => Confetti::shapes('triangle'))
            ->toThrow(InvalidShape::class, "'square', 'circle', 'star'");
    });

    it('accepts the three built-ins as strings or enum cases', function (): void {
        $payload = Confetti::shapes('star', ConfettiShape::Circle)->toArray();

        expect(burstOption($payload, 'shapes'))->toBe(['star', 'circle']);
    });

    it('requires a path matrix to be exactly six finite numbers', function (): void {
        expect(fn () => Confetti::shapeFromPath('M0 0 L1 1z', [1, 0, 0]))
            ->toThrow(InvalidShape::class, 'exactly 6 finite numbers');

        expect(fn () => Confetti::shapeFromPath('M0 0 L1 1z', [1, 0, 0, 1, 0, 'x']))
            ->toThrow(InvalidShape::class, 'only finite numbers');
    });

    it('rejects an empty path or text', function (): void {
        expect(fn () => Confetti::shapeFromPath('  '))->toThrow(InvalidShape::class);
        expect(fn () => Confetti::shapeFromText(' '))->toThrow(InvalidShape::class);
    });
});

describe('numeric options', function (): void {
    it('requires decay to sit strictly inside 0 and 1', function (): void {
        // It is a per-frame velocity multiplier: at 1 the particles never slow
        // down and the burst runs out its entire tick budget at full speed.
        expect(fn () => Confetti::decay(1.0))->toThrow(InvalidOption::class, 'greater than 0 and less than 1');
        expect(fn () => Confetti::decay(0.0))->toThrow(InvalidOption::class);
        expect(fn () => Confetti::decay(0.9))->not->toThrow(InvalidOption::class);
    });

    it('caps particle counts and tick budgets', function (): void {
        expect(fn () => Confetti::count(100000))->toThrow(InvalidOption::class, 'between 0 and 1000');
        expect(fn () => Confetti::ticks(999999))->toThrow(InvalidOption::class);
    });

    it('normalises an angle into a single turn', function (): void {
        expect(burstOption(Confetti::angle(450)->toArray(), 'angle'))->toBe(90.0);
        expect(burstOption(Confetti::angle(-90)->toArray(), 'angle'))->toBe(270.0);
    });

    it('allows an origin outside the viewport', function (): void {
        // Load-bearing: the fireworks and snow recipes both launch from a
        // negative y so particles fall in from above the fold. Clamping to
        // 0..1 would quietly break both.
        $payload = Confetti::origin(0.5, -0.2)->toArray();

        expect(burstOption($payload, 'origin'))->toBe(['x' => 0.5, 'y' => -0.2]);
    });

    it('allows negative gravity, which floats particles upward', function (): void {
        expect(fn () => Confetti::gravity(-1.0))->not->toThrow(InvalidOption::class);
    });

    it('rejects a value that is not a finite number', function (): void {
        expect(fn () => Confetti::spread(NAN))->toThrow(InvalidOption::class, 'finite');
        expect(fn () => Confetti::gravity(INF))->toThrow(InvalidOption::class);
    });
});

it('lets a caller catch every confetti failure as one family', function (): void {
    // Each exception extends the SPL type that describes it, so the marker
    // interface is what makes "any confetti problem" catchable in one clause.
    foreach ([
        fn () => Confetti::colors('red'),
        fn () => Confetti::shapes('triangle'),
        fn () => Confetti::decay(2.0),
    ] as $call) {
        try {
            $call();
            expect()->fail('Expected the call to throw.');
        } catch (Throwable $e) {
            expect($e)->toBeInstanceOf(ConfettiException::class);
        }
    }
});
