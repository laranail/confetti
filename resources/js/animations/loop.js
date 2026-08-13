/**
 * The animation driver every continuous effect runs on.
 *
 * Deliberately built on `requestAnimationFrame` rather than `setInterval`, even
 * for the interval-based fireworks recipe. Browsers throttle or suspend timers
 * in a background tab, and `setInterval` compensates by firing the backlog all
 * at once when the tab is focused again — coming back to a page and being met
 * with sixty simultaneous fireworks. An rAF clock with an accumulator simply
 * does not advance while the tab is hidden.
 *
 * The elapsed clock excludes hidden time, so a fifteen-second effect is fifteen
 * seconds of *visible* effect rather than fifteen seconds of wall time that the
 * visitor may have spent elsewhere.
 */

/**
 * @param {object}   spec
 * @param {number}   spec.duration        Total run time in milliseconds.
 * @param {Function} spec.onFrame         Called as `(elapsed, dt)`; return false to stop early.
 * @param {?number}  spec.interval        Fire on this cadence rather than every frame.
 * @param {AbortSignal} [spec.signal]
 * @param {boolean}  [spec.pauseWhenHidden=true]
 * @returns {Promise<void>} resolves when the loop finishes or is aborted.
 */
export function runLoop({ duration, onFrame, interval = null, signal, pauseWhenHidden = true }) {
  return new Promise((resolve) => {
    if (signal?.aborted) {
      resolve()

      return
    }

    const raf =
      typeof requestAnimationFrame === 'function'
        ? requestAnimationFrame
        : (callback) => setTimeout(() => callback(Date.now()), 16)

    let elapsed = 0
    let sinceLastFire = 0
    let previous = null
    let handle = null
    let finished = false

    const finish = () => {
      if (finished) return

      finished = true

      if (handle !== null && typeof cancelAnimationFrame === 'function') {
        cancelAnimationFrame(handle)
      }

      signal?.removeEventListener?.('abort', finish)

      resolve()
    }

    signal?.addEventListener?.('abort', finish, { once: true })

    const hidden = () =>
      pauseWhenHidden && typeof document !== 'undefined' && document.visibilityState === 'hidden'

    const frame = (timestamp) => {
      if (finished) return

      const now = typeof timestamp === 'number' ? timestamp : Date.now()

      if (previous === null) previous = now

      const dt = Math.max(0, now - previous)
      previous = now

      // A hidden tab advances neither the clock nor the effect, so nothing
      // accumulates while the page is out of sight.
      if (hidden()) {
        handle = raf(frame)

        return
      }

      elapsed += dt

      let keepGoing = true

      if (interval === null) {
        keepGoing = onFrame(elapsed, dt) !== false
      } else {
        sinceLastFire += dt

        while (sinceLastFire >= interval && keepGoing) {
          sinceLastFire -= interval
          keepGoing = onFrame(elapsed, dt) !== false
        }
      }

      if (!keepGoing || elapsed >= duration) {
        finish()

        return
      }

      handle = raf(frame)
    }

    handle = raf(frame)
  })
}

/** A float in `[min, max)`, matching the upstream recipes' randomInRange. */
export function randomInRange(min, max) {
  return Math.random() * (max - min) + min
}
