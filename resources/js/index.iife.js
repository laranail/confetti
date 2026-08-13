/**
 * Browser entry point: the bundle served by the route, publish and CDN modes.
 *
 * Boots itself and exposes `window.LaranailConfetti`, so a page that only wants
 * to fire confetti from its own script has an API to call:
 *
 *     LaranailConfetti.burst({ particleCount: 200, spread: 90 })
 *     LaranailConfetti.stop()
 *
 * No framework is required. Alpine, Livewire and Inertia adapters attach when
 * those are present and are inert when they are not.
 */
import { registerAlpineAdapter } from './adapters/alpine.js'
import { registerInertiaAdapter } from './adapters/inertia.js'
import { registerLivewireAdapter } from './adapters/livewire.js'
import { animationNames, registerAnimation } from './animations/index.js'
import { readBootConfig } from './core/boot.js'
import { ERROR_EVENT } from './core/errors.js'
import { beforeFire, EVENTS, off, on } from './core/events.js'
import { Runtime } from './core/runtime.js'
import { clearShapeCache } from './core/shapes.js'

const version = typeof __CONFETTI_VERSION__ === 'string' ? __CONFETTI_VERSION__ : 'dev'

let runtime = null

function boot() {
  if (runtime) return runtime

  runtime = new Runtime(readBootConfig())

  runtime.listen()

  registerLivewireAdapter(runtime)
  registerInertiaAdapter(runtime)

  // Alpine may not have been defined yet when this module runs; alpine:init is
  // the hook for that, and is simply never dispatched on a page without Alpine.
  registerAlpineAdapter(runtime)
  window.addEventListener('alpine:init', () => registerAlpineAdapter(runtime), { once: true })

  runtime.fireBootPayload()

  return runtime
}

const api = {
  version,
  ERROR_EVENT,
  EVENTS,

  /**
   * Subscribe to a runtime event, by key or full name.
   *
   *     LaranailConfetti.on('burst', (e) => console.log(e.detail.options))
   *
   * Returns its own unsubscribe function, so a listener added on a soft
   * navigation can be removed without keeping the handler around.
   */
  on(event, handler) {
    return on(event, handler)
  },

  off(event, handler) {
    off(event, handler)
  },

  /**
   * Transform every burst before it fires, for the things only the browser
   * knows: viewport size, theme, whether the tab is busy.
   *
   *     LaranailConfetti.beforeFire((o) => ({ ...o, particleCount: o.particleCount / 2 }))
   */
  beforeFire(hook) {
    return beforeFire(hook)
  },

  /** Fire a full payload, in the shape the server sends. */
  fire(payload) {
    boot().fire(payload)
  },

  /** Fire a single burst of canvas-confetti options. */
  burst(options = {}) {
    boot().fire({ v: 1, action: 'fire', bursts: [{ delay: 0, options }], animations: [] })
  },

  /** Run a continuous effect: fireworks, snow or schoolPride. */
  animate(animation, { duration = 15000, options = {}, params = {} } = {}) {
    boot().fire({ v: 1, action: 'fire', bursts: [], animations: [{ animation, duration, options, params }] })
  },

  stop() {
    boot().stop()
  },

  reset() {
    boot().reset()
  },

  registerAnimation(name, handler) {
    registerAnimation(name, handler)
  },

  animations: animationNames,

  clearShapeCache,

  runtime: () => boot(),
}

if (typeof window !== 'undefined') {
  window.LaranailConfetti = api

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true })
  } else {
    boot()
  }
}

export default api
