import { fireworks } from './fireworks.js'
import { schoolPride } from './school-pride.js'
import { snow } from './snow.js'

/**
 * The animation registry.
 *
 * Applications can add their own with `LaranailConfetti.registerAnimation()`,
 * and PHP can emit a matching descriptor through a custom preset.
 */
const animations = new Map([
  ['fireworks', fireworks],
  ['snow', snow],
  ['schoolPride', schoolPride],
])

export function registerAnimation(name, handler) {
  animations.set(name, handler)
}

export function getAnimation(name) {
  return animations.get(name) ?? null
}

export function animationNames() {
  return [...animations.keys()]
}

export { fireworks, schoolPride, snow }
