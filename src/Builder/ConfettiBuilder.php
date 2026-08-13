<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Builder;

use Simtabi\Laranail\Confetti\Builder\Concerns\AppliesPresets;
use Simtabi\Laranail\Confetti\Builder\Concerns\ConfiguresAccessibility;
use Simtabi\Laranail\Confetti\Builder\Concerns\ConfiguresAppearance;
use Simtabi\Laranail\Confetti\Builder\Concerns\ConfiguresOrigin;
use Simtabi\Laranail\Confetti\Builder\Concerns\ConfiguresParticles;
use Simtabi\Laranail\Confetti\Builder\Concerns\ConfiguresTiming;
use Simtabi\Laranail\Confetti\Builder\Concerns\DispatchesConfetti;
use Simtabi\Laranail\Confetti\Enums\PresetExpansion;
use Simtabi\Laranail\Confetti\Enums\ReducedMotionPolicy;
use Simtabi\Laranail\Confetti\Payload\PayloadDraft;
use Simtabi\Laranail\Confetti\Presets\PresetRegistry;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\Transport\TransportManager;
use Simtabi\Laranail\Confetti\Validation\OptionValidator;

/**
 * Describes a confetti effect and sends it.
 *
 *     Confetti::realistic()->shoot();
 *     Confetti::colors('#bb0000', '#ffffff')->schoolPride(8000)->shoot();
 *     Confetti::make()->topLeft()->count(80)->then()->topRight()->count(80)->shoot();
 *
 * The builder is **mutable**, which is load-bearing rather than an oversight —
 * accumulating bursts in a loop is a documented usage, and each iteration has
 * to see the previous one's work:
 *
 *     $confetti = Confetti::make();
 *     foreach ([0, 100, 200] as $delay) {
 *         $confetti->center()->delay($delay)->then();
 *     }
 *     $confetti->shoot();
 *
 * An immutable builder would discard every iteration silently. The payload
 * objects it produces are immutable instead, and `__clone` deep-copies the
 * mutable state so a configured builder can safely be used as a template.
 *
 * @see OptionStack for how options are layered.
 */
class ConfettiBuilder
{
    use AppliesPresets;
    use ConfiguresAccessibility;
    use ConfiguresAppearance;
    use ConfiguresOrigin;
    use ConfiguresParticles;
    use ConfiguresTiming;
    use DispatchesConfetti;

    protected OptionStack $stack;

    protected PayloadDraft $draft;

    protected int $delay = 0;

    protected int $stagger = 0;

    protected ?int $duration = null;

    protected ?int $seed = null;

    /** A driver name rather than an enum case, so custom drivers work too. */
    protected ?string $driver = null;

    protected ?PresetExpansion $expansion = null;

    protected ?ReducedMotionPolicy $reducedMotion = null;

    /**
     * Whether anything has been set since the last commit.
     *
     * Because options carry forward through `then()`, "the stack is not empty"
     * cannot mean "there is a burst waiting" — after a loop of `then()` calls
     * the stack is full but everything in it has already been committed.
     * Tracking the change explicitly is what stops that loop producing one
     * burst too many.
     */
    protected bool $dirty = false;

    public function __construct(
        protected readonly ConfettiConfig $config,
        protected readonly OptionValidator $validator,
        protected readonly PresetRegistry $presets,
        protected readonly TransportManager $transports,
    ) {
        $this->stack = new OptionStack($this->config->resolvedDefaults());
        $this->draft = new PayloadDraft;
    }

    /**
     * Deep-copy the mutable state so clones do not share a stack or a draft.
     */
    public function __clone(): void
    {
        $this->stack = clone $this->stack;
        $this->draft = clone $this->draft;
    }

    /** Set a canvas-confetti option directly, bypassing the typed setters. */
    public function option(string $key, mixed $value): static
    {
        return $this->setOption($key, $value);
    }

    /**
     * The options a burst would currently use, defaults included.
     *
     * @return array<string, mixed>
     */
    public function resolvedOptions(): array
    {
        return $this->stack->resolve();
    }

    public function config(): ConfettiConfig
    {
        return $this->config;
    }

    /** The reduced-motion policy this effect will be sent with. */
    public function reducedMotionPolicy(): ReducedMotionPolicy
    {
        return $this->reducedMotion ?? $this->config->reducedMotion;
    }

    protected function setOption(string $key, mixed $value): static
    {
        $this->stack->setUser($key, $value);
        $this->dirty = true;

        return $this;
    }
}
