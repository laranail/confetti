/**
 * Error reporting.
 *
 * The implementation this replaces did `confetti(config).catch(() => {})`, which
 * was wrong twice over. It swallowed everything, so a shape that could not be
 * built produced no confetti and no explanation. And canvas-confetti returns
 * `null` rather than a promise when there is no global `Promise`, so `.catch`
 * would itself throw.
 *
 * The failures that actually happen here are synchronous: `shapeFromText`
 * throws a TypeError without `OffscreenCanvas`, `shapeFromPath` throws without
 * `Path2D` or `DOMMatrix`, and the promise canvas-confetti returns never
 * rejects at all. So the sync path is the one that matters; the async catch is
 * defensive.
 *
 * Every failure is logged and dispatched as `confetti:error`, so an application
 * can forward it to its own error reporting in a couple of lines.
 */

export const ERROR_EVENT = 'confetti:error'

/**
 * Report a failure without letting it escape.
 *
 * @param {unknown} error
 * @param {object} [context]
 * @returns {null}
 */
export function report(error, context = {}) {
  const detail = { error, ...context }

  // eslint-disable-next-line no-console
  console.error('[laranail/confetti]', error, context)

  try {
    if (typeof window !== 'undefined' && typeof window.dispatchEvent === 'function') {
      window.dispatchEvent(new CustomEvent(ERROR_EVENT, { detail }))
    }
  } catch {
    // A browser too old for CustomEvent still gets the console message.
  }

  return null
}

/**
 * Warn once per message. Used for advice that would otherwise repeat on every
 * frame of an animation.
 */
const warned = new Set()

export function warnOnce(message) {
  if (warned.has(message)) return

  warned.add(message)

  // eslint-disable-next-line no-console
  console.warn('[laranail/confetti]', message)
}

export function resetWarnings() {
  warned.clear()
}
