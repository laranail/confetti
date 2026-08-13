import confetti from 'canvas-confetti'

import { getAnimation, registerAnimation } from '../animations/index.js'
import { readBootConfig } from './boot.js'
import { report } from './errors.js'
import { createCannon, fireBursts } from './fire.js'
import { normalizePayload } from './normalize.js'
import { shouldSkip } from './reduced-motion.js'
import { clearShapeCache, configureShapeCache } from './shapes.js'

/**
 * The runtime.
 *
 * Plain JavaScript with no framework behind it. It reads a JSON block, listens
 * on `window`, and calls canvas-confetti — which is all that is needed for
 * Blade, Livewire, Inertia and Filament alike, because Livewire dispatches its
 * events on `window` too.
 *
 * The Alpine adapter is optional and additive; nothing here depends on it.
 */
export class Runtime {
  constructor(boot = {}) {
    this.boot = boot
    this.runtime = boot.runtime || {}
    this.cannon = createCannon(this.runtime)
    this.animations = new Set()
    this.seen = new Set()
    this.listeners = []

    configureShapeCache({ size: this.runtime.shapeCacheSize })
  }

  /** Fire a payload in whatever shape it arrived. */
  fire(raw) {
    const payload = normalizePayload(raw)

    if (!payload) return

    if (payload.action === 'stop' || payload.action === 'reset') {
      this.stop()

      if (payload.action === 'reset') this.reset()

      return
    }

    // A page restored from the back/forward cache re-runs its boot script, and
    // a soft navigation can hand us the same payload twice. Without an id there
    // is no way to tell either from a genuine second effect.
    if (payload.id) {
      if (this.seen.has(payload.id)) return

      this.seen.add(payload.id)
    }

    const policy = payload.reducedMotion || this.runtime.reducedMotion || 'reduce'

    if (shouldSkip(policy)) return

    const context = {
      cannon: this.cannon,
      defaults: this.boot.defaults || {},
      policy,
      pauseWhenHidden: this.runtime.pauseWhenHidden !== false,
    }

    if (payload.bursts.length > 0) {
      fireBursts(payload.bursts, context)
    }

    for (const descriptor of payload.animations) {
      this.animate(descriptor, context)
    }
  }

  /** Start one continuous effect. */
  animate(descriptor, context) {
    const handler = getAnimation(descriptor.animation)

    if (!handler) {
      report(new Error(`Unknown confetti animation "${descriptor.animation}".`), {
        phase: 'animate',
      })

      return
    }

    // A page that fires a fifteen-second effect on every action would otherwise
    // stack them until the tab gives up. The oldest is dropped.
    const limit = this.runtime.maxConcurrentAnimations ?? 3

    while (this.animations.size >= limit) {
      const oldest = this.animations.values().next().value
      oldest.abort()
      this.animations.delete(oldest)
    }

    const controller = new AbortController()

    this.animations.add(controller)

    const done = () => this.animations.delete(controller)

    Promise.resolve(handler(descriptor, { ...context, signal: controller.signal }))
      .then(done)
      .catch((error) => {
        done()
        report(error, { phase: 'animate', animation: descriptor.animation })
      })
  }

  /**
   * Abort every running animation.
   *
   * The reason the adapters exist: a fifteen-second snowfall started before a
   * `wire:navigate` would otherwise keep falling over the next page.
   */
  stop() {
    for (const controller of this.animations) {
      controller.abort()
    }

    this.animations.clear()
  }

  /** Stop everything and clear the canvas. */
  reset() {
    this.stop()

    try {
      if (typeof this.cannon.reset === 'function') {
        this.cannon.reset()
      } else if (typeof confetti.reset === 'function') {
        confetti.reset()
      }
    } catch (error) {
      report(error, { phase: 'reset' })
    }
  }

  /** Start listening for payloads dispatched from the server. */
  listen(target = typeof window !== 'undefined' ? window : null) {
    if (!target) return this

    const handler = (event) => {
      // Livewire dispatches with positional arguments, so a single object
      // argument arrives wrapped in an array.
      const detail = event.detail
      const payload = Array.isArray(detail) ? detail[0] : detail

      this.fire(payload)
    }

    for (const name of [this.boot.event, this.boot.legacyEvent]) {
      if (!name) continue

      target.addEventListener(name, handler)
      this.listeners.push([target, name, handler])
    }

    return this
  }

  /** Fire whatever the server put in the boot block. */
  fireBootPayload() {
    if (this.boot.payload) this.fire(this.boot.payload)

    return this
  }

  /** Re-read the page after a soft navigation and fire anything new. */
  refresh() {
    const boot = readBootConfig()

    this.boot = { ...this.boot, ...boot, runtime: this.runtime }

    if (boot.payload) this.fire(boot.payload)

    return this
  }

  registerAnimation(name, handler) {
    registerAnimation(name, handler)

    return this
  }

  /** Detach listeners, abort animations and release cached bitmaps. */
  destroy() {
    this.stop()
    clearShapeCache()

    for (const [target, name, handler] of this.listeners) {
      target.removeEventListener(name, handler)
    }

    this.listeners = []
  }
}

export function createRuntime(boot = readBootConfig()) {
  return new Runtime(boot)
}
