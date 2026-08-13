/**
 * ES module entry point.
 *
 * Deliberately does not boot itself. An application importing this into its own
 * bundle decides when to start, which matters when Alpine or Livewire has to be
 * configured first.
 *
 *     import { start } from '@laranail/confetti'
 *
 *     start()
 *
 * The IIFE entry point boots automatically; this one does not.
 */
export { registerAlpineAdapter } from './adapters/alpine.js'
export { registerInertiaAdapter } from './adapters/inertia.js'
export { registerLivewireAdapter } from './adapters/livewire.js'
export { animationNames, getAnimation, registerAnimation } from './animations/index.js'
export { readBootConfig } from './core/boot.js'
export { ERROR_EVENT } from './core/errors.js'
export { beforeFire, emit, EVENTS, off, on } from './core/events.js'
export { fire, fireBursts } from './core/fire.js'
export { normalizePayload } from './core/normalize.js'
export { createRuntime, Runtime } from './core/runtime.js'
export { clearShapeCache, resolveShapes } from './core/shapes.js'

import { registerAlpineAdapter } from './adapters/alpine.js'
import { registerInertiaAdapter } from './adapters/inertia.js'
import { registerLivewireAdapter } from './adapters/livewire.js'
import { readBootConfig } from './core/boot.js'
import { Runtime } from './core/runtime.js'

export const version = typeof __CONFETTI_VERSION__ === 'string' ? __CONFETTI_VERSION__ : 'dev'

/**
 * Create a runtime, wire up the optional adapters, and fire anything the server
 * already put on the page.
 */
export function start(options = {}) {
  const runtime = new Runtime(options.boot ?? readBootConfig())

  runtime.listen()

  registerLivewireAdapter(runtime)
  registerInertiaAdapter(runtime, { prop: options.inertiaProp ?? 'confetti' })
  registerAlpineAdapter(runtime)

  runtime.fireBootPayload()

  return runtime
}
