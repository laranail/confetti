import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('canvas-confetti', () => {
  const confetti = vi.fn(() => Promise.resolve())
  confetti.create = vi.fn(() => confetti)
  confetti.reset = vi.fn()
  confetti.shapeFromText = vi.fn(() => ({ type: 'bitmap', bitmap: { close: vi.fn() } }))
  confetti.shapeFromPath = vi.fn(() => ({ type: 'path' }))

  return { default: confetti }
})

const { Runtime } = await import('../../resources/js/core/runtime.js')
const { readBootConfig } = await import('../../resources/js/core/boot.js')
const confetti = (await import('canvas-confetti')).default

const payload = (overrides = {}) => ({
  v: 1,
  id: '01ABC',
  action: 'fire',
  bursts: [{ delay: 0, options: { particleCount: 10 } }],
  animations: [],
  ...overrides,
})

describe('boot config', () => {
  beforeEach(() => {
    document.body.innerHTML = ''
  })

  it('reads the JSON data block', () => {
    document.body.innerHTML = `
      <script type="application/json" data-confetti-boot>
        {"event":"custom:fire","defaults":{"spread":70}}
      </script>`

    const boot = readBootConfig()

    expect(boot.event).toBe('custom:fire')
    expect(boot.defaults).toEqual({ spread: 70 })
    // Runtime keys not present in the block keep their defaults.
    expect(boot.runtime.reducedMotion).toBe('reduce')
  })

  it('falls back to defaults when the page has no block, so the runtime works standalone', () => {
    expect(readBootConfig().event).toBe('confetti:fire')
  })

  it('survives a malformed block', () => {
    vi.spyOn(console, 'error').mockImplementation(() => {})
    document.body.innerHTML = '<script type="application/json" data-confetti-boot>{oops</script>'

    expect(readBootConfig().event).toBe('confetti:fire')
  })
})

describe('runtime', () => {
  beforeEach(() => {
    confetti.mockClear()
    window.matchMedia = vi.fn(() => ({ matches: false, addEventListener: vi.fn(), removeEventListener: vi.fn() }))
  })

  it('fires the bursts in a payload', () => {
    new Runtime({ defaults: {} }).fire(payload())

    expect(confetti).toHaveBeenCalledOnce()
  })

  it('ignores a payload it has already seen', () => {
    // A page restored from the back/forward cache re-runs its boot script.
    // Without the id there is no way to tell that from a second effect.
    const runtime = new Runtime({ defaults: {} })

    runtime.fire(payload())
    runtime.fire(payload())

    expect(confetti).toHaveBeenCalledOnce()
  })

  it('treats a payload with a different id as a new effect', () => {
    const runtime = new Runtime({ defaults: {} })

    runtime.fire(payload({ id: 'one' }))
    runtime.fire(payload({ id: 'two' }))

    expect(confetti).toHaveBeenCalledTimes(2)
  })

  it('listens on both the configured and the legacy event names', () => {
    const runtime = new Runtime({ event: 'confetti:fire', legacyEvent: 'fire-confetti', defaults: {} })
    runtime.listen(window)

    window.dispatchEvent(new CustomEvent('fire-confetti', { detail: payload({ id: 'legacy' }) }))

    expect(confetti).toHaveBeenCalledOnce()

    runtime.destroy()
  })

  it('unwraps the array Livewire dispatches a single argument in', () => {
    const runtime = new Runtime({ event: 'confetti:fire', defaults: {} })
    runtime.listen(window)

    window.dispatchEvent(new CustomEvent('confetti:fire', { detail: [payload({ id: 'wrapped' })] }))

    expect(confetti).toHaveBeenCalledOnce()

    runtime.destroy()
  })

  it('aborts running animations on a stop payload', async () => {
    const runtime = new Runtime({ defaults: {} })

    runtime.fire({
      v: 1,
      id: 'anim',
      action: 'fire',
      bursts: [],
      animations: [{ animation: 'snow', duration: 60000, options: {}, params: {} }],
    })

    expect(runtime.animations.size).toBe(1)

    runtime.fire({ v: 1, id: 'stop', action: 'stop', bursts: [], animations: [] })

    expect(runtime.animations.size).toBe(0)
  })

  it('drops the oldest animation once the concurrency limit is reached', () => {
    const runtime = new Runtime({ defaults: {}, runtime: { maxConcurrentAnimations: 2 } })

    for (const id of ['a', 'b', 'c']) {
      runtime.fire({
        v: 1,
        id,
        action: 'fire',
        bursts: [],
        animations: [{ animation: 'snow', duration: 60000, options: {}, params: {} }],
      })
    }

    expect(runtime.animations.size).toBe(2)

    runtime.stop()
  })

  it('reports an unknown animation instead of failing silently', () => {
    const error = vi.spyOn(console, 'error').mockImplementation(() => {})

    new Runtime({ defaults: {} }).fire({
      v: 1,
      id: 'x',
      action: 'fire',
      bursts: [],
      animations: [{ animation: 'nope', duration: 1000 }],
    })

    expect(error).toHaveBeenCalledOnce()
  })
})

describe('debug logging', () => {
  beforeEach(() => {
    confetti.mockClear()
    window.matchMedia = vi.fn(() => ({ matches: true, addEventListener: vi.fn(), removeEventListener: vi.fn() }))
  })

  it('says nothing unless runtime.debug is set', () => {
    const info = vi.spyOn(console, 'info').mockImplementation(() => {})

    new Runtime({ defaults: {}, runtime: { reducedMotion: 'skip' } }).fire(payload())

    expect(info).not.toHaveBeenCalled()
  })

  it('explains the quiet outcomes when it is', () => {
    // Confetti fired, nothing drawn, no error raised. Without this the three
    // legitimate reasons for that are indistinguishable from a broken install.
    const info = vi.spyOn(console, 'info').mockImplementation(() => {})

    const runtime = new Runtime({ defaults: {}, runtime: { debug: true, reducedMotion: 'skip' } })
    runtime.fire(payload())

    expect(info).toHaveBeenCalledWith(
      '[laranail/confetti]',
      'Suppressed by the reduced-motion policy.',
      { policy: 'skip' },
    )
  })

  it('reports a payload it has already fired', () => {
    const info = vi.spyOn(console, 'info').mockImplementation(() => {})
    window.matchMedia = vi.fn(() => ({ matches: false, addEventListener: vi.fn(), removeEventListener: vi.fn() }))

    const runtime = new Runtime({ defaults: {}, runtime: { debug: true } })
    runtime.fire(payload({ id: 'twice' }))
    runtime.fire(payload({ id: 'twice' }))

    expect(info).toHaveBeenCalledWith(
      '[laranail/confetti]',
      'Ignored a payload already fired.',
      { id: 'twice' },
    )
  })
})
