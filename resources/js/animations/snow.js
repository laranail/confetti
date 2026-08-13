import { fire } from '../core/fire.js'
import { randomInRange, runLoop } from './loop.js'

/**
 * The upstream "Snow" recipe.
 *
 * One particle per frame, launched with no velocity so gravity alone carries
 * it, each with its own weight, size and sideways drift.
 *
 * The `skew` term is what stops it looking mechanical. It starts at 1 and
 * creeps toward 0.8, narrowing the band flakes are born in, so the snowfall
 * appears to settle in rather than switching on at full strength. The tick
 * budget shrinks alongside the remaining duration so the last flakes fade out
 * instead of being cut off mid-fall.
 */
export function snow(descriptor, context) {
  const { duration = 15000, options = {}, params = {} } = descriptor
  const ticksMin = params.ticksMin ?? 200
  const ticksMax = params.ticksMax ?? 500
  const skewTo = params.skewTo ?? 0.8
  const skewStep = params.skewStep ?? 0.001
  const [gravityMin, gravityMax] = params.gravity ?? [0.4, 0.6]
  const [scalarMin, scalarMax] = params.scalar ?? [0.4, 1]
  const [driftMin, driftMax] = params.drift ?? [-0.4, 0.4]

  let skew = params.skewFrom ?? 1

  return runLoop({
    duration,
    signal: context.signal,
    pauseWhenHidden: context.pauseWhenHidden,
    onFrame: (elapsed) => {
      const timeLeft = duration - elapsed

      if (timeLeft <= 0) return false

      skew = Math.max(skewTo, skew - skewStep)

      fire(
        {
          ...options,
          ticks: Math.max(ticksMin, ticksMax * (timeLeft / duration)),
          origin: { x: Math.random(), y: Math.random() * skew - 0.2 },
          gravity: randomInRange(gravityMin, gravityMax),
          scalar: randomInRange(scalarMin, scalarMax),
          drift: randomInRange(driftMin, driftMax),
        },
        context,
      )

      return true
    },
  })
}
