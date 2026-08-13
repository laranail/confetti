import confetti from 'canvas-confetti'

import { report } from './errors.js'
import { mergeOptions } from './normalize.js'
import { reduceBurst, shouldReduce, shouldSkip } from './reduced-motion.js'
import { resolveShapes } from './shapes.js'

/**
 * The one place `confetti()` is called.
 *
 * Everything funnels through here so shape resolution, the reduced-motion gate
 * and error handling cannot be forgotten at a call site.
 */

/**
 * Build the cannon.
 *
 * The default export is not the same as `confetti.create()` with no arguments.
 * it is pre-built with `useWorker: true` and `resize: true`, and `create()`
 * defaults both to false. So a custom canvas has to opt back in explicitly, or
 * it renders at the canvas's intrinsic size and stretches.
 *
 * canvas-confetti also ignores `zIndex` on a canvas it did not create, and
 * applies none of its own positioning, so the element is styled here instead.
 */
export function createCannon(runtime = {}) {
  const selector = runtime.canvas

  if (!selector) return confetti

  const element = typeof document !== 'undefined' ? document.querySelector(selector) : null

  if (!element) {
    report(new Error(`No element matched the configured confetti canvas selector "${selector}".`), {
      phase: 'canvas',
    })

    return confetti
  }

  // Reusing an initialised canvas throws, so the instance is kept on the node.
  if (element.__laranailConfetti) return element.__laranailConfetti

  Object.assign(element.style, {
    position: element.style.position || 'fixed',
    inset: element.style.inset || '0',
    pointerEvents: 'none',
    zIndex: element.style.zIndex || String(runtime.zIndex ?? 100),
  })

  const cannon = confetti.create(element, {
    resize: true,
    useWorker: runtime.useWorker !== false,
  })

  element.__laranailConfetti = cannon

  return cannon
}

/**
 * Fire one burst.
 *
 * @returns {Promise|null} whatever canvas-confetti returned, so callers can
 *   chain, but note its promise resolves when the animation finishes and never
 *   rejects.
 */
export function fire(options, context) {
  const { cannon = confetti, defaults = {}, policy = 'reduce' } = context || {}

  if (shouldSkip(policy)) return null

  let merged = mergeOptions(defaults, options)

  if (shouldReduce(policy)) merged = reduceBurst(merged)

  const shapes = resolveShapes(merged.shapes)

  if (shapes) {
    merged.shapes = shapes
  } else {
    delete merged.shapes
  }

  let result

  try {
    // The real failures land here: an unbuildable shape, a canvas already
    // owned by another instance.
    result = cannon(merged)
  } catch (error) {
    return report(error, { phase: 'fire', options: merged })
  }

  // Guard the return value: canvas-confetti hands back null when there is no
  // global Promise, and the previous implementation called .catch on it.
  if (result && typeof result.then === 'function') {
    result.catch((error) => report(error, { phase: 'settle', options: merged }))
  }

  return result
}

/** Fire a list of bursts, honouring each one's delay. */
export function fireBursts(bursts, context) {
  const { policy = 'reduce' } = context || {}

  if (shouldSkip(policy)) return []

  // Under a reduced-motion preference, the first burst stands in for the whole
  // sequence; nine emoji volleys is exactly what the preference is asking us
  // not to do.
  const list = shouldReduce(policy) ? bursts.slice(0, 1) : bursts

  return list.map((burst) => {
    const delay = burst.delay ?? 0

    if (delay <= 0) return fire(burst.options, context)

    const timer = setTimeout(() => fire(burst.options, context), delay)

    context?.signal?.addEventListener?.('abort', () => clearTimeout(timer), { once: true })

    return null
  })
}
