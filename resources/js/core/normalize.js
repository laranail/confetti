/**
 * Turns whatever arrived into the shape the runtime works with.
 *
 * Two things need normalising. Payloads carry only the options that differ from
 * the package defaults, so the defaults have to be merged back in, using the same
 * precedence PHP applies:
 *
 *     canvas-confetti built-ins  <  boot defaults  <  burst options
 *
 * And a payload flashed to the session before an upgrade is still sitting there
 * after it. The old format was a bare array of option objects with the delay
 * mixed in among them, so that shape is accepted and lifted into the current
 * envelope rather than being dropped on the floor.
 */

export const SUPPORTED_VERSION = 1

/**
 * @returns {{ v: number, id: ?string, action: string, bursts: Array, animations: Array, reducedMotion: ?string }|null}
 */
export function normalizePayload(raw) {
  if (!raw) return null

  // Legacy: a bare array of option objects, each possibly carrying `delay`.
  if (Array.isArray(raw)) {
    return {
      v: 0,
      id: null,
      action: 'fire',
      bursts: raw.map(legacyBurst).filter(Boolean),
      animations: [],
      reducedMotion: null,
    }
  }

  if (typeof raw !== 'object') return null

  // A payload from a newer package than this bundle. Refusing is better than
  // guessing at fields we do not understand.
  if (typeof raw.v === 'number' && raw.v > SUPPORTED_VERSION) {
    // eslint-disable-next-line no-console
    console.error(
      `[laranail/confetti] Payload version ${raw.v} is newer than this runtime supports ` +
        `(${SUPPORTED_VERSION}). Rebuild the package assets.`,
    )

    return null
  }

  return {
    v: raw.v ?? 0,
    id: typeof raw.id === 'string' ? raw.id : null,
    action: typeof raw.action === 'string' ? raw.action : 'fire',
    bursts: Array.isArray(raw.bursts) ? raw.bursts.map(normalizeBurst).filter(Boolean) : [],
    animations: Array.isArray(raw.animations) ? raw.animations.filter(Boolean) : [],
    reducedMotion: typeof raw.reducedMotion === 'string' ? raw.reducedMotion : null,
  }
}

function normalizeBurst(burst) {
  if (!burst || typeof burst !== 'object') return null

  return {
    delay: typeof burst.delay === 'number' ? burst.delay : 0,
    options: burst.options && typeof burst.options === 'object' ? burst.options : {},
  }
}

function legacyBurst(entry) {
  if (!entry || typeof entry !== 'object') return null

  const { delay, ...options } = entry

  return { delay: typeof delay === 'number' ? delay : 0, options }
}

/**
 * Merge the boot defaults underneath a burst's own options.
 *
 * `delay` is stripped; it schedules the call and is not a canvas-confetti
 * option; passing it through would be harmless but pointless.
 */
export function mergeOptions(defaults, options) {
  const merged = { ...(defaults || {}), ...(options || {}) }

  delete merged.delay

  return merged
}
