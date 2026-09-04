<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\View\ConfettiTags;
use Simtabi\Laranail\Confetti\Events\ConfettiFired;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\Support\EffectRegistry;
use Simtabi\Laranail\Confetti\Builder\ConfettiBuilder;
use Simtabi\Laranail\Confetti\Events\ConfettiRendered;
use Simtabi\Laranail\Confetti\Events\ConfettiPreparing;
use Simtabi\Laranail\Confetti\Exceptions\InvalidEffect;
use Simtabi\Laranail\Confetti\Confetti as ConfettiService;

afterEach(fn () => app(ConfettiService::class)->forgetHooks());

describe('named effects', function (): void {
    it('fires an effect declared in config', function (): void {
        // The point of the indirection: the call site says what it means, and
        // what it looks like is a config decision.
        expect(Confetti::effect('celebrate')->toArray()['bursts'])->toHaveCount(5);
    });

    it('applies every option in the definition', function (): void {
        $payload = Confetti::effect('subtle')->toArray();

        expect(burstOption($payload, 'particleCount'))->toBe(40);
        expect(burstOption($payload, 'spread'))->toBe(45.0);
        expect(burstOption($payload, 'ticks'))->toBe(120);
        expect(burstOption($payload, 'origin'))->toBe(['x' => 0.5, 'y' => 0.0]);
    });

    it('combines a preset with a palette', function (): void {
        $payload = Confetti::effect('award')->toArray();

        expect($payload['bursts'])->toHaveCount(6);
        expect(burstOption($payload, 'colors'))
            ->toBe(['#ffe400', '#ffbd00', '#e89400', '#ffca6c', '#fdffb8']);
    });

    it('passes a duration through to a continuous effect', function (): void {
        expect(Confetti::effect('party')->toArray()['animations'][0]['duration'])->toBe(6000);
    });

    it('is still a builder, so the caller can keep going', function (): void {
        $payload = Confetti::effect('subtle')->count(99)->toArray();

        expect(burstOption($payload, 'particleCount'))->toBe(99);
    });

    it('names the configured effects when one is unknown', function (): void {
        expect(fn () => Confetti::effect('nope'))
            ->toThrow(InvalidEffect::class, "Configured effects: 'celebrate'");
    });

    it('rejects an option that is not a builder method', function (): void {
        // Silently ignoring it is how a config file ends up describing
        // behaviour the package does not have.
        $registry = new EffectRegistry(['broken' => ['particleCount' => 10]]);

        expect(fn (): ConfettiBuilder => $registry->apply('broken', Confetti::make()))
            ->toThrow(InvalidEffect::class, "'count' rather than 'particleCount'");
    });

    it('spreads a list into separate arguments', function (): void {
        $registry = new EffectRegistry(['point' => ['origin' => [0.25, 0.75]]]);

        $payload = $registry->apply('point', Confetti::make())->toArray();

        expect(burstOption($payload, 'origin'))->toBe(['x' => 0.25, 'y' => 0.75]);
    });
});

describe('hooks', function (): void {
    it('applies a hook to every effect', function (): void {
        Confetti::before(fn (ConfettiBuilder $b): ConfettiBuilder => $b->palette('gold'));

        expect(burstOption(Confetti::make()->toArray(), 'colors'))
            ->toBe(['#ffe400', '#ffbd00', '#e89400', '#ffca6c', '#fdffb8']);
    });

    it('lets the caller override what a hook set', function (): void {
        // Hooks run when the builder is made, so a later call wins. That order
        // is what makes a hook a house style rather than a straitjacket.
        Confetti::before(fn (ConfettiBuilder $b): ConfettiBuilder => $b->count(10));

        expect(burstOption(Confetti::count(500)->toArray(), 'particleCount'))->toBe(500);
    });

    it('runs hooks in the order they were registered', function (): void {
        $order = [];

        Confetti::before(function () use (&$order): void {
            $order[] = 'first';
        });
        Confetti::before(function () use (&$order): void {
            $order[] = 'second';
        });

        Confetti::make();

        expect($order)->toBe(['first', 'second']);
    });
});

describe('events', function (): void {
    it('announces a builder before anything is set on it', function (): void {
        Event::fake([ConfettiPreparing::class]);

        Confetti::make();

        Event::assertDispatched(ConfettiPreparing::class);
    });

    it('lets a listener apply policy to every effect', function (): void {
        Event::listen(ConfettiPreparing::class, function (ConfettiPreparing $event): void {
            $event->builder->reducedMotion('skip');
        });

        expect(Confetti::realistic()->toArray()['reducedMotion'])->toBe('skip');
    });

    it('announces a fired payload with the transport that carried it', function (): void {
        Event::fake([ConfettiFired::class]);
        Confetti::fake();

        Confetti::realistic()->shoot();

        Event::assertDispatched(
            ConfettiFired::class,
            fn (ConfettiFired $e): bool => $e->payload->burstCount() === 5,
        );
    });

    it('announces where the markup was rendered from', function (): void {
        // Distinguishes "the runtime never reached the page" from "a payload
        // never arrived", which look identical in a browser.
        Event::fake([ConfettiRendered::class]);

        app(ConfettiTags::class)->render('filament');

        Event::assertDispatched(
            ConfettiRendered::class,
            fn (ConfettiRendered $e): bool => $e->source === 'filament' && $e->hasPayload === false,
        );
    });

    it('says when the rendered markup carries a payload', function (): void {
        Event::fake([ConfettiRendered::class]);

        $this->startSession();
        Confetti::count(7)->shoot();

        app()->forgetInstance(ConfettiConfig::class);
        app(ConfettiTags::class)->render();

        Event::assertDispatched(
            ConfettiRendered::class,
            fn (ConfettiRendered $e): bool => $e->hasPayload,
        );
    });
});
