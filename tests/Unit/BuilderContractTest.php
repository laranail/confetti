<?php

declare(strict_types=1);

use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Support\EffectRegistry;
use Simtabi\Laranail\Confetti\Builder\ConfettiBuilder;

/**
 * Every public builder method must do something observable.
 *
 * `duration()` shipped as a no-op: it wrote to a property nothing read, so
 * `snow()->duration(5000)` ran for the default fifteen seconds and said nothing
 * about it. Static analysis cannot see that, because the property *was* written
 * and *was* declared, and a unit test only catches it if someone thought to
 * write one for that method.
 *
 * So this asserts the property behaviourally, for the whole surface at once:
 * call a method, and the payload has to change. The exemption list is the other
 * half. A method that genuinely does not alter the payload has to be named
 * there with a reason, and any method in neither list fails the run, so a new
 * setter cannot be added without someone deciding which it is.
 */

/**
 * How to exercise one method.
 *
 * `control` and `variant` receive a fresh builder. The variant must produce a
 * different payload. Both are closures rather than a method name and arguments
 * so that ordering-sensitive methods, like `stagger()` before the `then()` calls
 * it spaces, can be expressed at all.
 *
 * @return array<string, array{0: Closure(ConfettiBuilder): ConfettiBuilder, 1: Closure(ConfettiBuilder): ConfettiBuilder}>
 */
function builderContract(): array
{
    $same = static fn (ConfettiBuilder $b): ConfettiBuilder => $b;

    return [
        // Particles
        'count'         => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->count(37)],
        'spread'        => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->spread(123)],
        'angle'         => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->angle(37)],
        'startVelocity' => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->startVelocity(37)],
        'decay'         => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->decay(0.37)],
        'gravity'       => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->gravity(0.37)],
        'drift'         => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->drift(0.37)],
        'ticks'         => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->ticks(37)],
        'scalar'        => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->scalar(1.37)],
        'flat'          => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->flat()],
        // Not 100: that is the configured default, so it would be a legitimate
        // no-op on the wire and would test nothing.
        'zIndex' => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->zIndex(37)],
        'option' => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->option('customKey', 'v')],

        // Position
        'origin'      => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->origin(0.37, 0.37)],
        'originX'     => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->originX(0.37)],
        'originY'     => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->originY(0.37)],
        'position'    => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->position('top-left')],
        'center'      => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->center()],
        'top'         => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->top()],
        'bottom'      => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->bottom()],
        'left'        => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->left()],
        'right'       => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->right()],
        'topLeft'     => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->topLeft()],
        'topRight'    => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->topRight()],
        'bottomLeft'  => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->bottomLeft()],
        'bottomRight' => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->bottomRight()],

        // Appearance
        'colors'        => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->colors('#123456')],
        'palette'       => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->palette('gold')],
        'shapes'        => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->shapes('star')],
        'shapeFromPath' => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->shapeFromPath('M0 0 L1 1z')],
        'shapeFromText' => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->shapeFromText('!')],

        // Timing
        'delay' => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->delay(370)],
        // The regression this file exists for. Only observable on an animation,
        // and only via a route that does not take a duration argument.
        'duration' => [
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->snow(),
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->snow()->duration(4321),
        ],
        // Only observable across a commit, which is the point of it.
        'stagger' => [
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->left()->then()->right(),
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->stagger(370)->left()->then()->right(),
        ],

        // Accessibility
        'disableForReducedMotion' => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->disableForReducedMotion()],
        'reducedMotion'           => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->reducedMotion('skip')],
        'skipForReducedMotion'    => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->skipForReducedMotion()],

        // Presets
        'preset'      => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->preset('success')],
        'stars'       => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->stars()],
        'success'     => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->success()],
        'magic'       => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->magic()],
        'rain'        => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->rain()],
        'realistic'   => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->realistic()],
        'emoji'       => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->emoji('!')],
        'fireworks'   => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->fireworks()],
        'snow'        => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->snow()],
        'schoolPride' => [$same, fn (ConfettiBuilder $b): ConfettiBuilder => $b->schoolPride()],

        // Composition
        'then' => [
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->count(11),
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->count(11)->then()->count(22),
        ],
        'reset' => [
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->count(11),
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->count(11)->reset(),
        ],
        // Turns descriptors into bursts.
        'expand' => [
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->snow(600),
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->snow(600)->expand(),
        ],
        // Only observable once expansion is on, which is the only time it means
        // anything.
        'seed' => [
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->snow(600)->expand()->seed(1),
            fn (ConfettiBuilder $b): ConfettiBuilder => $b->snow(600)->expand()->seed(999),
        ],
    ];
}

/**
 * Methods that legitimately leave the payload alone.
 *
 * @return array<string, string>
 */
function builderExemptions(): array
{
    return [
        'shoot'               => 'Sends the payload; it does not describe it.',
        'via'                 => 'Chooses a transport. Covered by TransportResolutionTest.',
        'toPayload'           => 'Inspection.',
        'toArray'             => 'Inspection.',
        'toResolvedArray'     => 'Inspection.',
        'toJson'              => 'Inspection.',
        'resolvedOptions'     => 'Inspection.',
        'config'              => 'Accessor.',
        'reducedMotionPolicy' => 'Accessor.',
    ];
}

it('covers every public builder method', function (): void {
    // The forcing function. A new setter lands in neither list, so the run
    // fails until someone decides whether it is meant to change the payload.
    $reflection = new ReflectionClass(ConfettiBuilder::class);

    $public = array_values(array_filter(
        array_map(
            static fn (ReflectionMethod $m): string => $m->getName(),
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
        ),
        static fn (string $name): bool => ! str_starts_with($name, '__'),
    ));

    $classified = [...array_keys(builderContract()), ...array_keys(builderExemptions())];
    $unclassified = array_values(array_diff($public, $classified));

    expect($unclassified)->toBe([], sprintf(
        'Unclassified builder method(s): %s. Add each to builderContract() with a case '
        . 'proving it changes the payload, or to builderExemptions() with a reason.',
        implode(', ', $unclassified),
    ));

    // And the reverse, so the lists cannot rot once a method is removed.
    expect(array_values(array_diff($classified, $public)))->toBe([]);
});

it('makes every non-exempt builder method change the payload', function (): void {
    foreach (builderContract() as $method => [$control, $variant]) {
        $before = $control(Confetti::make())->toArray();
        $after = $variant(Confetti::make())->toArray();

        // The payload id is a fresh ULID every time and never equal.
        unset($before['id'], $after['id']);

        expect($after)->not->toBe($before, "{$method}() did not change the payload.");
    }
});

it('keeps the effect allowlist in step with the builder', function (): void {
    // An effect definition names a builder method, so the allowlist is a second
    // classification of the same surface and rots the same way. A new setter
    // that should be usable from config is useless until it is listed here, and
    // a method that disappears leaves a dangling entry.
    $reflection = new ReflectionClass(ConfettiBuilder::class);

    $public = array_map(
        static fn (ReflectionMethod $m): string => $m->getName(),
        $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
    );

    $allowed = EffectRegistry::allowedMethods();

    expect(array_values(array_diff($allowed, $public)))->toBe([], sprintf(
        'The effect allowlist names method(s) the builder no longer has: %s.',
        implode(', ', array_diff($allowed, $public)),
    ));

    // Everything the contract test treats as payload-changing is a setting, so
    // it belongs in the allowlist unless it is deliberately excluded.
    $configuring = array_keys(builderContract());

    $missing = array_values(array_diff($configuring, $allowed, [
        // Deliberately excluded: these decide when and how confetti is sent,
        // not what it looks like. See EffectRegistry::CONFIGURATION_METHODS.
        'then', 'reset', 'expand', 'seed',
    ]));

    expect($missing)->toBe([], sprintf(
        'Builder method(s) that configure an effect but cannot be used from config: %s. '
        . 'Add them to EffectRegistry::CONFIGURATION_METHODS, or to the exclusion list here '
        . 'if they belong at the call site.',
        implode(', ', $missing),
    ));
});
