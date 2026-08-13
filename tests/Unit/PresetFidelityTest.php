<?php

declare(strict_types=1);

use Simtabi\Laranail\Confetti\Facades\Confetti;

/**
 * The upstream canvas-confetti recipes are the specification for these presets,
 * so the numbers below are copied from them rather than from our own output.
 * If a value here changes, either upstream changed or the port drifted.
 */
it('fires the official realistic recipe as five simultaneous bursts', function (): void {
    $payload = Confetti::realistic()->toArray();

    expect($payload['bursts'])->toHaveCount(5);

    // Ratios .25/.2/.35/.1/.1 of 200 particles, floored.
    expect(array_map(fn (array $b) => $b['options']['particleCount'], $payload['bursts']))
        ->toBe([50, 40, 70, 20, 20]);

    // The origin sits above centre so the burst looks like it came from the
    // page rather than from nowhere.
    foreach ($payload['bursts'] as $burst) {
        expect($burst['options']['origin'])->toBe(['x' => 0.5, 'y' => 0.7]);
    }

    // All five fire at once. Staggering them would read as five small pops.
    expect(array_map(fn (array $b) => $b['delay'], $payload['bursts']))->toBe([0, 0, 0, 0, 0]);

    expect($payload['bursts'][0]['options'])->toMatchArray(['spread' => 26.0, 'startVelocity' => 55.0]);
    expect($payload['bursts'][2]['options'])->toMatchArray(['spread' => 100.0, 'decay' => 0.91, 'scalar' => 0.8]);
    expect($payload['bursts'][3]['options'])->toMatchArray(['startVelocity' => 25.0, 'decay' => 0.92, 'scalar' => 1.2]);
});

it('fires the official stars recipe, not merely a gold palette', function (): void {
    $payload = Confetti::stars()->toArray();

    // Three volleys of two bursts each.
    expect($payload['bursts'])->toHaveCount(6);
    expect(array_map(fn (array $b) => $b['delay'], $payload['bursts']))->toBe([0, 0, 100, 100, 200, 200]);

    // Zero gravity is what suspends them; without it this is just a burst.
    expect(burstOption($payload, 'gravity'))->toBe(0.0);
    expect(burstOption($payload, 'spread'))->toBe(360.0);
    expect(burstOption($payload, 'ticks'))->toBe(50);
    expect(burstOption($payload, 'decay'))->toBe(0.94);
    expect(burstOption($payload, 'startVelocity'))->toBe(30.0);

    expect(burstOption($payload, 'particleCount', 0))->toBe(40);
    expect(burstOption($payload, 'scalar', 0))->toBe(1.2);
    expect(burstOption($payload, 'shapes', 0))->toBe(['star']);

    expect(burstOption($payload, 'particleCount', 1))->toBe(10);
    expect(burstOption($payload, 'scalar', 1))->toBe(0.75);
    expect(burstOption($payload, 'shapes', 1))->toBe(['circle']);

    expect(burstOption($payload, 'colors'))
        ->toBe(['#ffe400', '#ffbd00', '#e89400', '#ffca6c', '#fdffb8']);
});

it('fires the official emoji recipe with the text scalar matching the burst scalar', function (): void {
    $payload = Confetti::emoji('🎉')->toArray();

    expect($payload['bursts'])->toHaveCount(9);
    expect(burstOption($payload, 'gravity'))->toBe(0.0);
    expect(burstOption($payload, 'decay'))->toBe(0.96);
    expect(burstOption($payload, 'ticks'))->toBe(60);
    expect(burstOption($payload, 'startVelocity'))->toBe(20.0);

    expect(burstOption($payload, 'particleCount', 0))->toBe(30);
    expect(burstOption($payload, 'particleCount', 1))->toBe(5);
    expect(burstOption($payload, 'flat', 1))->toBeTrue();
    expect(burstOption($payload, 'particleCount', 2))->toBe(15);
    expect(burstOption($payload, 'scalar', 2))->toBe(1.0);

    // canvas-confetti rasterises the glyph once at 10*scalar pixels and then
    // scales that bitmap by the burst's scalar. The two have to agree or the
    // emoji is drawn blurred and at the wrong size.
    $shape = burstOption($payload, 'shapes', 0)[0];

    expect($shape['type'])->toBe('text');
    expect($shape['text'])->toBe('🎉');
    expect($shape['scalar'])->toBe(burstOption($payload, 'scalar', 0));
});

it('sends fireworks with its own spread and ticks, not the package defaults', function (): void {
    // The bug this guards: an array_merge with its arguments reversed let the
    // generic defaults (spread 70, ticks 200) overwrite the preset's own
    // values, turning a 360-degree burst into a narrow puff that lingered three
    // times too long.
    $payload = Confetti::fireworks()->toArray();

    expect($payload['animations'])->toHaveCount(1);
    expect(animationOption($payload, 'spread'))->toBe(360.0);
    expect(animationOption($payload, 'ticks'))->toBe(60);
    expect(animationOption($payload, 'startVelocity'))->toBe(30.0);
    expect(animationOption($payload, 'zIndex'))->toBe(0);

    expect(animationParam($payload, 'interval'))->toBe(250);
    expect(animationParam($payload, 'particleCount'))->toBe(50);
    expect(animationParam($payload, 'xRanges'))->toBe([[0.1, 0.3], [0.7, 0.9]]);
});

it('applies presets independently of call order', function (): void {
    $before = Confetti::spread(90)->fireworks()->toArray();
    $after = Confetti::fireworks()->spread(90)->toArray();

    expect(animationOption($before, 'spread'))->toBe(90.0);
    expect(animationOption($after, 'spread'))->toBe(90.0);
});

it('sends snow as the official per-frame loop', function (): void {
    $payload = Confetti::snow()->toArray();

    expect($payload['animations'])->toHaveCount(1);
    expect($payload['animations'][0]['animation'])->toBe('snow');
    expect(animationOption($payload, 'particleCount'))->toBe(1);
    expect(animationOption($payload, 'startVelocity'))->toBe(0.0);
    expect(animationOption($payload, 'shapes'))->toBe(['circle']);
    expect(animationOption($payload, 'colors'))->toBe(['#ffffff']);

    // The skew drifting from 1 to 0.8 is what makes the snowfall settle in
    // rather than switch on.
    expect(animationParam($payload, 'skewFrom'))->toBe(1.0);
    expect(animationParam($payload, 'skewTo'))->toBe(0.8);
    expect(animationParam($payload, 'ticksMin'))->toBe(200);
    expect(animationParam($payload, 'ticksMax'))->toBe(500);
});

it('sends school pride as two inward-firing emitters', function (): void {
    $payload = Confetti::schoolPride()->toArray();

    expect(animationOption($payload, 'particleCount'))->toBe(2);
    expect(animationOption($payload, 'spread'))->toBe(55.0);
    expect(animationOption($payload, 'colors'))->toBe(['#bb0000', '#ffffff']);

    $sides = animationParam($payload, 'sides');

    expect($sides)->toHaveCount(2);
    expect($sides[0]['angle'])->toBe(60);
    expect($sides[0]['origin']['x'])->toBe(0.0);
    expect($sides[1]['angle'])->toBe(120);
    expect($sides[1]['origin']['x'])->toBe(1.0);
});

it('lets a caller override a preset colour in either order', function (): void {
    $before = Confetti::colors('#123456')->schoolPride()->toArray();
    $after = Confetti::schoolPride()->colors('#123456')->toArray();

    expect(animationOption($before, 'colors'))->toBe(['#123456']);
    expect(animationOption($after, 'colors'))->toBe(['#123456']);
});
