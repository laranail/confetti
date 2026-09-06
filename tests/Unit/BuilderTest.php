<?php

declare(strict_types=1);

use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Enums\ConfettiPosition;

it('sends only the options that differ from the defaults', function (): void {
    // The defaults travel once, in the boot payload. Repeating them in every
    // burst is what made the old five-burst realistic effect 1.4KB.
    $payload = Confetti::count(50)->toArray();

    expect(burstOption($payload, 'particleCount'))->toBe(50);
    expect($payload['bursts'][0]['options'])->not->toHaveKey('ticks');
    expect($payload['bursts'][0]['options'])->not->toHaveKey('colors');
});

it('resolves the defaults for inspection', function (): void {
    $resolved = Confetti::count(50)->toResolvedArray();

    expect($resolved['bursts'][0]['options'])->toMatchArray([
        'particleCount' => 50,
        'spread'        => 70,
        'ticks'         => 200,
    ]);
});

describe('then()', function (): void {
    it('commits a burst and carries the options forward', function (): void {
        // A palette set before the first burst should still apply to the second.
        // The old behaviour cleared everything, so colours silently vanished.
        $payload = Confetti::colors('#ff0000')->left()->then()->right()->toArray();

        expect($payload['bursts'])->toHaveCount(2);
        expect(burstOption($payload, 'colors', 0))->toBe(['#ff0000']);
        expect(burstOption($payload, 'colors', 1))->toBe(['#ff0000']);
        expect(burstOption($payload, 'origin', 0))->toBe(['x' => 0.0, 'y' => 0.5]);
        expect(burstOption($payload, 'origin', 1))->toBe(['x' => 1.0, 'y' => 0.5]);
    });

    it('clears everything when asked, matching the older behaviour', function (): void {
        $payload = Confetti::colors('#ff0000')->left()->then(reset: true)->right()->toArray();

        expect(burstOption($payload, 'colors', 1))->toBeNull();
    });

    it('supports the documented accumulate-in-a-loop idiom', function (): void {
        // The builder is mutable precisely so this works; an immutable one
        // would discard every iteration.
        $confetti = Confetti::make();

        foreach ([0, 100, 200, 300, 400] as $delay) {
            $confetti->center()->delay($delay)->then();
        }

        $payload = $confetti->toArray();

        expect($payload['bursts'])->toHaveCount(5);
        expect($payload['bursts'][4]['delay'])->toBe(400);
    });

    it('spaces bursts automatically with stagger()', function (): void {
        $payload = Confetti::make()->stagger(150)->left()->then()->right()->then()->center()->toArray();

        expect(array_column($payload['bursts'], 'delay'))->toBe([0, 150, 300]);
    });
});

describe('positions', function (): void {
    it('pairs each named position with an angle firing away from its edge', function (): void {
        $cases = [
            'topLeft'     => [['x' => 0.0, 'y' => 0.0], 315.0],
            'topRight'    => [['x' => 1.0, 'y' => 0.0], 225.0],
            'bottomLeft'  => [['x' => 0.0, 'y' => 1.0], 60.0],
            'bottomRight' => [['x' => 1.0, 'y' => 1.0], 120.0],
            'top'         => [['x' => 0.5, 'y' => 0.0], 270.0],
            'bottom'      => [['x' => 0.5, 'y' => 1.0], 90.0],
        ];

        foreach ($cases as $method => [$origin, $angle]) {
            $payload = Confetti::{$method}()->toArray();

            expect(burstOption($payload, 'origin'))->toBe($origin, $method);
            expect(burstOption($payload, 'angle'))->toBe($angle, $method);
        }
    });

    it('leaves the angle alone at the centre, so a centred burst can still be aimed', function (): void {
        $payload = Confetti::angle(45)->center()->toArray();

        expect(burstOption($payload, 'angle'))->toBe(45.0);
        expect(burstOption($payload, 'origin'))->toBe(['x' => 0.5, 'y' => 0.5]);
    });

    it('accepts a position enum or its string value', function (): void {
        expect(burstOption(Confetti::position(ConfettiPosition::TopLeft)->toArray(), 'origin'))
            ->toBe(burstOption(Confetti::position('top-left')->toArray(), 'origin'));
    });

    it('moves one axis at a time', function (): void {
        $payload = Confetti::origin(0.2, 0.8)->originX(0.9)->toArray();

        expect(burstOption($payload, 'origin'))->toBe(['x' => 0.9, 'y' => 0.8]);
    });
});

it('does not share state between clones', function (): void {
    $base = Confetti::make()->colors('#ff0000');

    $left = (clone $base)->left();
    $right = (clone $base)->right();

    expect(burstOption($left->toArray(), 'origin'))->toBe(['x' => 0.0, 'y' => 0.5]);
    expect(burstOption($right->toArray(), 'origin'))->toBe(['x' => 1.0, 'y' => 0.5]);
});

it('uses a named palette', function (): void {
    $payload = Confetti::palette('gold')->toArray();

    expect(burstOption($payload, 'colors'))
        ->toBe(['#ffe400', '#ffbd00', '#e89400', '#ffca6c', '#fdffb8']);
});

it('accepts an arbitrary canvas-confetti option', function (): void {
    expect(burstOption(Confetti::option('custom', 'value')->toArray(), 'custom'))->toBe('value');
});
