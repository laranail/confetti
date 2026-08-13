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
const { fire } = await import('../../resources/js/core/fire.js')
const { EVENTS, beforeFire, clearHooks, on } = await import('../../resources/js/core/events.js')
const confetti = (await import('canvas-confetti')).default

const payload = (overrides = {}) => ({
  v: 1,
  id: 'evt-' + Math.random(),
  action: 'fire',
  bursts: [{ delay: 0, options: { particleCount: 10 } }],
  animations: [],
  ...overrides,
})

/** Collect one event's details for the duration of a test. */
function capture(event) {
  const seen = []
  const stop = on(event, (e) => seen.push(e.detail))

  return { seen, stop }
}

beforeEach(() => {
  confetti.mockClear()
  clearHooks()
  window.matchMedia = vi.fn(() => ({ matches: false, addEventListener: vi.fn(), removeEventListener: vi.fn() }))
})

describe('lifecycle events', () => {
  it('announces every burst with the options it fired', () => {
    const { seen, stop } = capture(EVENTS.burst)

    new Runtime({ defaults: { spread: 70 } }).fire(payload())

    expect(seen).toHaveLength(1)
    expect(seen[0].options.particleCount).toBe(10)
    // The defaults were merged in before the event, so a listener sees what
    // canvas-confetti saw rather than the sparse wire form.
    expect(seen[0].options.spread).toBe(70)
    expect(seen[0].reduced).toBe(false)

    stop()
  })

  it('announces the runtime booting', () => {
    const { seen, stop } = capture(EVENTS.booted)

    new Runtime({ event: 'confetti:fire', defaults: {} }).fireBootPayload()

    expect(seen).toHaveLength(1)
    expect(seen[0].hasPayload).toBe(false)

    stop()
  })

  it('brackets an animation with a start and an end', async () => {
    const started = capture(EVENTS.animationStart)
    const ended = capture(EVENTS.animationEnd)

    const runtime = new Runtime({ defaults: {} })

    runtime.fire({
      v: 1,
      id: 'anim',
      action: 'fire',
      bursts: [],
      animations: [{ animation: 'snow', duration: 50, options: {}, params: {} }],
    })

    expect(started.seen).toHaveLength(1)
    expect(started.seen[0].animation).toBe('snow')

    runtime.stop()
    await Promise.resolve()

    expect(ended.seen.length).toBeGreaterThan(0)

    started.stop()
    ended.stop()
  })

  it('announces a stop, with how many effects it aborted', () => {
    const { seen, stop } = capture(EVENTS.stopped)

    const runtime = new Runtime({ defaults: {} })

    runtime.fire({
      v: 1,
      id: 'a',
      action: 'fire',
      bursts: [],
      animations: [{ animation: 'snow', duration: 9000, options: {}, params: {} }],
    })
    runtime.stop()

    expect(seen).toHaveLength(1)
    expect(seen[0].animations).toBe(1)

    stop()
  })

  it('does not announce a stop when nothing was running', () => {
    const { seen, stop } = capture(EVENTS.stopped)

    new Runtime({ defaults: {} }).stop()

    expect(seen).toHaveLength(0)

    stop()
  })
})

describe('skipped events', () => {
  it('says when reduced motion suppressed an effect', () => {
    window.matchMedia = vi.fn(() => ({ matches: true, addEventListener: vi.fn(), removeEventListener: vi.fn() }))
    const { seen, stop } = capture(EVENTS.skipped)

    new Runtime({ defaults: {}, runtime: { reducedMotion: 'skip' } }).fire(payload())

    expect(seen[0].reason).toBe('reduced-motion')

    stop()
  })

  it('says when a payload had already fired', () => {
    const { seen, stop } = capture(EVENTS.skipped)

    const runtime = new Runtime({ defaults: {} })
    const p = payload({ id: 'same' })

    runtime.fire(p)
    runtime.fire(p)

    expect(seen[0].reason).toBe('already-fired')

    stop()
  })

  it('says when an animation was dropped at the concurrency cap', () => {
    const { seen, stop } = capture(EVENTS.skipped)

    const runtime = new Runtime({ defaults: {}, runtime: { maxConcurrentAnimations: 1 } })

    for (const id of ['a', 'b']) {
      runtime.fire({
        v: 1,
        id,
        action: 'fire',
        bursts: [],
        animations: [{ animation: 'snow', duration: 9000, options: {}, params: {} }],
      })
    }

    expect(seen.some((d) => d.reason === 'concurrency-cap')).toBe(true)

    runtime.stop()
    stop()
  })
})

describe('beforeFire hooks', () => {
  it('transforms the options every burst fires with', () => {
    beforeFire((options) => ({ ...options, particleCount: 1 }))

    fire({ particleCount: 500 }, { cannon: confetti, policy: 'ignore' })

    expect(confetti).toHaveBeenCalledWith(expect.objectContaining({ particleCount: 1 }))
  })

  it('leaves the options alone when a hook returns nothing', () => {
    // Returning undefined is the shape of a hook that only inspects, and it
    // must not wipe the burst.
    beforeFire(() => {})

    fire({ particleCount: 42 }, { cannon: confetti, policy: 'ignore' })

    expect(confetti).toHaveBeenCalledWith(expect.objectContaining({ particleCount: 42 }))
  })

  it('reports a throwing hook and fires anyway', () => {
    vi.spyOn(console, 'error').mockImplementation(() => {})
    const { seen, stop } = capture(EVENTS.error)

    beforeFire(() => {
      throw new Error('hook exploded')
    })

    fire({ particleCount: 7 }, { cannon: confetti, policy: 'ignore' })

    expect(seen[0].phase).toBe('beforeFire')
    expect(confetti).toHaveBeenCalledWith(expect.objectContaining({ particleCount: 7 }))

    stop()
  })

  it('unsubscribes when its returned function is called', () => {
    const remove = beforeFire((options) => ({ ...options, particleCount: 1 }))
    remove()

    fire({ particleCount: 99 }, { cannon: confetti, policy: 'ignore' })

    expect(confetti).toHaveBeenCalledWith(expect.objectContaining({ particleCount: 99 }))
  })
})

describe('subscription helper', () => {
  it('accepts a short key or the full event name', () => {
    const byKey = vi.fn()
    const byName = vi.fn()

    const a = on('burst', byKey)
    const b = on('confetti:burst', byName)

    new Runtime({ defaults: {} }).fire(payload())

    expect(byKey).toHaveBeenCalledOnce()
    expect(byName).toHaveBeenCalledOnce()

    a()
    b()
  })

  it('stops delivering once unsubscribed', () => {
    const handler = vi.fn()
    const stop = on('burst', handler)

    stop()

    new Runtime({ defaults: {} }).fire(payload())

    expect(handler).not.toHaveBeenCalled()
  })
})
