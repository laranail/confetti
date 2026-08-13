import { fire } from '../core/fire.js'
import { randomInRange, runLoop } from './loop.js'

/**
 * The upstream "Fireworks" recipe.
 *
 * A pair of 360-degree bursts every 250ms, one on each side, with the particle
 * count falling in step with the time remaining so the display tapers rather
 * than stopping dead.
 *
 * Launch heights run from -0.2 to 0.8, above the top of the viewport at one
 * end. Particles fall, so starting them all on screen would put every firework
 * in the bottom half of it.
 */
export function fireworks(descriptor, context) {
  const { duration = 15000, options = {}, params = {} } = descriptor
  const interval = params.interval ?? 250
  const peak = params.particleCount ?? 50
  const xRanges = params.xRanges ?? [
    [0.1, 0.3],
    [0.7, 0.9],
  ]
  const [yMin, yMax] = params.yRange ?? [-0.2, 0.8]

  return runLoop({
    duration,
    interval,
    signal: context.signal,
    pauseWhenHidden: context.pauseWhenHidden,
    onFrame: (elapsed) => {
      const timeLeft = duration - elapsed

      if (timeLeft <= 0) return false

      const particleCount = Math.floor(peak * (timeLeft / duration))

      if (particleCount <= 0) return true

      for (const [min, max] of xRanges) {
        fire(
          {
            ...options,
            particleCount,
            origin: { x: randomInRange(min, max), y: randomInRange(yMin, yMax) },
          },
          context,
        )
      }

      return true
    },
  })
}
