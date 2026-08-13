/**
 * Reads the boot configuration out of the page.
 *
 * The server writes a `<script type="application/json" data-confetti-boot>`
 * block. A script element whose type is not a JavaScript MIME type is never
 * executed, so this is inert data: no `unsafe-inline` in the CSP, and no need
 * for Alpine or any other framework to carry the payload onto the page, which
 * is what an earlier version of this package relied on.
 */

export const BOOT_SELECTOR = 'script[type="application/json"][data-confetti-boot]'

export const DEFAULT_BOOT = {
  event: 'confetti:fire',
  legacyEvent: 'fire-confetti',
  defaults: {},
  runtime: {
    useWorker: true,
    canvas: null,
    reducedMotion: 'reduce',
    pauseWhenHidden: true,
    maxConcurrentAnimations: 3,
    shapeCacheSize: 32,
    debug: false,
  },
  payload: null,
}

/**
 * Read and parse the boot block.
 *
 * Returns the defaults when the page has none, so a runtime loaded by hand
 * (imported into an application's own bundle, say) still works with no markup
 * from this package at all.
 */
export function readBootConfig(root = typeof document !== 'undefined' ? document : null) {
  if (!root || typeof root.querySelector !== 'function') return { ...DEFAULT_BOOT }

  const node = root.querySelector(BOOT_SELECTOR)

  if (!node) return { ...DEFAULT_BOOT }

  try {
    const parsed = JSON.parse(node.textContent || '{}')

    return {
      ...DEFAULT_BOOT,
      ...parsed,
      runtime: { ...DEFAULT_BOOT.runtime, ...(parsed.runtime || {}) },
    }
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error('[laranail/confetti] Could not parse the boot payload.', error)

    return { ...DEFAULT_BOOT }
  }
}

/**
 * Read the boot block again after a soft navigation.
 *
 * Livewire and Inertia swap the body without a page load, so the element is a
 * new one carrying the next page's payload.
 */
export function refreshBootPayload(root = typeof document !== 'undefined' ? document : null) {
  return readBootConfig(root).payload
}
