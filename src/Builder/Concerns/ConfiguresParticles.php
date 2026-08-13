<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Builder\Concerns;

/**
 * The numeric options describing how particles behave.
 *
 * Every one maps to a canvas-confetti option of the same name, except `count()`
 * which sets `particleCount`; the shorter name reads better in a chain and
 * matches the original package's API.
 *
 * Values are validated here, in the setter, so a bad number is reported at the
 * line that supplied it rather than several frames later during serialisation.
 */
trait ConfiguresParticles
{
    /** How many particles this burst launches. */
    public function count(int $count): static
    {
        return $this->setOption('particleCount', $this->validator->particleCount($count));
    }

    /** How wide the burst fans out, in degrees. */
    public function spread(float|int $spread): static
    {
        return $this->setOption('spread', $this->validator->spread($spread));
    }

    /**
     * Which way the burst fires, in degrees: 90 is straight up, 0 is to the
     * right, and the value increases anticlockwise.
     */
    public function angle(float|int $angle): static
    {
        return $this->setOption('angle', $this->validator->angle($angle));
    }

    /**
     * How fast particles leave the origin.
     *
     * Each particle gets a random velocity between half and one and a half
     * times this, so bursts never look uniform. Negative values fire inward,
     * which is how confetti is made to fall from above the viewport.
     */
    public function startVelocity(float|int $velocity): static
    {
        return $this->setOption('startVelocity', $this->validator->startVelocity($velocity));
    }

    /**
     * How quickly particles slow down. This is a per-frame velocity
     * multiplier, so it must sit strictly between 0 and 1. Lower is stickier.
     */
    public function decay(float|int $decay): static
    {
        return $this->setOption('decay', $this->validator->decay($decay));
    }

    /**
     * How hard particles fall. canvas-confetti triples this internally, so 1 is
     * a brisk fall rather than a gentle one. Negative values float upward.
     */
    public function gravity(float|int $gravity): static
    {
        return $this->setOption('gravity', $this->validator->gravity($gravity));
    }

    /** Sideways pull. Negative drifts left, positive right. */
    public function drift(float|int $drift): static
    {
        return $this->setOption('drift', $this->validator->drift($drift));
    }

    /**
     * How many frames a particle lives.
     *
     * Also drives the fade: opacity is one minus the fraction of the budget
     * spent, so shortening this makes particles vanish sooner *and* faster.
     */
    public function ticks(int $ticks): static
    {
        return $this->setOption('ticks', $this->validator->ticks($ticks));
    }

    /** Particle size, where 1 is the default 10 pixels. */
    public function scalar(float|int $scalar): static
    {
        return $this->setOption('scalar', $this->validator->scalar($scalar));
    }

    /**
     * Draw particles flat, without the tumbling that suggests depth.
     *
     * Mostly useful with text particles, where a spinning emoji reads as a
     * glitch rather than as motion.
     */
    public function flat(bool $flat = true): static
    {
        return $this->setOption('flat', $flat);
    }

    /**
     * Stacking order for the canvas the library creates.
     *
     * Ignored when the runtime draws onto a canvas you supplied, because
     * canvas-confetti only styles the element it made itself. Configure `runtime.canvas` and the
     * package applies the z-index for you.
     */
    public function zIndex(int $zIndex): static
    {
        return $this->setOption('zIndex', $this->validator->zIndex($zIndex));
    }
}
