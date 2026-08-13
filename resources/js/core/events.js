/**
 * The runtime's event vocabulary.
 *
 * Everything is a `CustomEvent` on `window`, so a page can listen with the
 * platform API and nothing has to import this module:
 *
 *     window.addEventListener('confetti:burst', (e) => console.log(e.detail))
 *
 * `on()` is sugar over the same thing, and returns its own unsubscribe function
 * so a listener attached during a soft navigation can be removed on the way out
 * without keeping a reference to the handler.
 *
 * One naming rule matters. `confetti:fire` is the *inbound* event the runtime
 * listens to, the one the server dispatches. Everything the runtime emits uses
 * a different name, so a listener can never accidentally re-trigger the effect
 * it is observing.
 */

export const EVENTS = {
  /** The runtime has read its boot config and is listening. */
  booted: 'confetti:booted',
  /** One burst was handed to canvas-confetti. */
  burst: 'confetti:burst',
  /** A continuous effect started. */
  animationStart: 'confetti:animation-start',
  /** A continuous effect finished or was aborted. */
  animationEnd: 'confetti:animation-end',
  /** Nothing was drawn, and why. */
  skipped: 'confetti:skipped',
  /** Everything running was aborted. */
  stopped: 'confetti:stopped',
  /** Something failed. Carries `{ error, phase, ... }`. */
  error: 'confetti:error',
}

/** Dispatch one runtime event. Never throws; a listener must not break an effect. */
export function emit(name, detail = {}) {
  if (typeof window === 'undefined' || typeof window.dispatchEvent !== 'function') {
    return
  }

  try {
    window.dispatchEvent(new CustomEvent(name, { detail }))
  } catch {
    // A browser without CustomEvent still gets the effect, just not the event.
  }
}

/**
 * Subscribe to a runtime event.
 *
 * Accepts either a key from EVENTS or the full event name, so both of these
 * work and neither needs the constant imported:
 *
 *     LaranailConfetti.on('burst', handler)
 *     LaranailConfetti.on('confetti:burst', handler)
 *
 * @returns {Function} unsubscribe
 */
export function on(name, handler, target = typeof window !== 'undefined' ? window : null) {
  const event = EVENTS[name] ?? name

  if (!target || typeof target.addEventListener !== 'function') {
    return () => {}
  }

  target.addEventListener(event, handler)

  return () => target.removeEventListener(event, handler)
}

export function off(name, handler, target = typeof window !== 'undefined' ? window : null) {
  const event = EVENTS[name] ?? name

  target?.removeEventListener?.(event, handler)
}

/**
 * Transforms applied to a burst's options before it fires.
 *
 * The browser-side counterpart to the PHP `before()` hook, for the settings
 * only the client knows: the viewport, the theme, whether the tab is busy.
 * Each hook receives the options and returns them, and returning nothing
 * leaves them untouched rather than wiping them.
 */
const hooks = []

export function beforeFire(hook) {
  hooks.push(hook)

  return () => {
    const i = hooks.indexOf(hook)

    if (i !== -1) hooks.splice(i, 1)
  }
}

export function applyBeforeFire(options) {
  return hooks.reduce((carry, hook) => {
    try {
      return hook(carry) ?? carry
    } catch (error) {
      emit(EVENTS.error, { error, phase: 'beforeFire' })

      return carry
    }
  }, options)
}

export function clearHooks() {
  hooks.length = 0
}
