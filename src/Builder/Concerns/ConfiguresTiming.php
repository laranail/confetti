<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Builder\Concerns;

/**
 * When bursts fire.
 *
 * `delay` is not a canvas-confetti option; it is stripped from the burst before
 * the library sees it and used by the runtime to schedule the call. Bursts
 * default to no delay, so a multi-burst preset fires as one composite effect
 * rather than a trickle.
 */
trait ConfiguresTiming
{
    /** Wait this many milliseconds before firing this burst. */
    public function delay(int $milliseconds): static
    {
        $this->delay = $this->validator->delay($milliseconds);
        $this->dirty = true;

        return $this;
    }

    /** How long a continuous effect runs, in milliseconds. */
    public function duration(int $milliseconds): static
    {
        $this->duration = $this->validator->duration($milliseconds);

        return $this;
    }

    /**
     * Space subsequent bursts evenly.
     *
     * Once set, each `then()` advances the delay by this amount, so a chain of
     * bursts arrives as a sequence without threading a counter through the call
     * site.
     */
    public function stagger(int $milliseconds): static
    {
        $this->stagger = $this->validator->delay($milliseconds);

        return $this;
    }
}
