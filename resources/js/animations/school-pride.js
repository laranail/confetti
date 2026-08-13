import { fire } from '../core/fire.js'
import { runLoop } from './loop.js'

/**
 * The upstream "School Pride" recipe.
 *
 * Two particles per frame from each side, angled at 60 and 120 degrees so the
 * streams arc toward the middle. Two particles sounds like nothing; at sixty
 * frames a second it is a steady jet.
 *
 * The emitters come from `params.sides` rather than being hard-coded, so a
 * three- or four-sided variant is configuration and not a new effect.
 */
export function schoolPride(descriptor, context) {
  const { duration = 15000, options = {}, params = {} } = descriptor
  const sides = params.sides ?? [
    { angle: 60, origin: { x: 0, y: 0.5 } },
    { angle: 120, origin: { x: 1, y: 0.5 } },
  ]

  return runLoop({
    duration,
    signal: context.signal,
    pauseWhenHidden: context.pauseWhenHidden,
    onFrame: (elapsed) => {
      if (duration - elapsed <= 0) return false

      for (const side of sides) {
        fire({ ...options, angle: side.angle, origin: side.origin }, context)
      }

      return true
    },
  })
}
