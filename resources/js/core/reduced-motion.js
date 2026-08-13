/**
 * The reduced-motion gate.
 *
 * canvas-confetti has an option for this, but it evaluates the media query once
 * when it builds its cannon and caches the answer — so passing the option on a
 * later burst does nothing. We forward it for completeness and check the query
 * ourselves before every fire, which also means someone changing the setting
 * mid-session is respected without a reload.
 *
 * Three policies:
 *
 *   ignore  Fire everything. Only appropriate when the confetti *is* the page.
 *   reduce  Collapse an animation to one short burst at half strength, and drop
 *           the trailing bursts of a multi-burst effect. The default: it keeps
 *           the acknowledgement without the fifteen seconds of motion.
 *   skip    Draw nothing.
 */

export const IGNORE = 'ignore'
export const REDUCE = 'reduce'
export const SKIP = 'skip'

/** Checked per call, not cached — the preference can change mid-session. */
export function prefersReducedMotion() {
  if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
    return false
  }

  try {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches
  } catch {
    return false
  }
}

export function shouldSkip(policy) {
  return policy === SKIP && prefersReducedMotion()
}

export function shouldReduce(policy) {
  return policy === REDUCE && prefersReducedMotion()
}

/**
 * Soften a burst for a visitor who asked for less motion: half the particles,
 * and a tick budget short enough that it is over quickly.
 */
export function reduceBurst(options) {
  const particleCount = Math.max(1, Math.floor((options.particleCount ?? 50) / 2))

  return {
    ...options,
    particleCount,
    ticks: Math.min(options.ticks ?? 200, 100),
  }
}
