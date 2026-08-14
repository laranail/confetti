/**
 * Alpine adapter. Entirely optional.
 *
 * The runtime needs no framework; this only adds sugar for pages that already
 * have Alpine, so confetti can be fired from markup:
 *
 *     <button x-data="laranailConfetti" @click="realistic()">Celebrate</button>
 *
 * Registered only when Alpine is already present. Alpine is never imported or
 * loaded here; shipping a second copy alongside the one Livewire bundles is a
 * well-known way to break a page.
 *
 * The name is camelCase rather than the `laranail-confetti` used everywhere
 * else, because Alpine evaluates the `x-data` attribute as a JavaScript
 * expression: `x-data="laranail-confetti"` is a subtraction of two undefined
 * names, not a component.
 *
 * Only named methods are exposed, so the component works under the CSP build of
 * Alpine, which refuses arbitrary expressions in markup.
 */
export function registerAlpineAdapter(runtime, alpine = typeof window !== 'undefined' ? window.Alpine : null) {
  if (!alpine || typeof alpine.data !== 'function') return runtime

  alpine.data('laranailConfetti', () => ({
    fire(payload) {
      runtime.fire(payload)
    },
    burst(options = {}) {
      runtime.fire({ v: 1, action: 'fire', bursts: [{ delay: 0, options }], animations: [] })
    },
    preset(name, options = {}) {
      runtime.fire({
        v: 1,
        action: 'fire',
        bursts: [],
        animations: [{ animation: name, duration: options.duration ?? 15000, options, params: {} }],
      })
    },
    realistic() {
      this.burst({ particleCount: 150, spread: 70, origin: { x: 0.5, y: 0.7 } })
    },
    stop() {
      runtime.stop()
    },
    reset() {
      runtime.reset()
    },
  }))

  return runtime
}
