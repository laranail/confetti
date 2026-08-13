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
