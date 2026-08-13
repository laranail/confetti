import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('canvas-confetti', () => {
  const confetti = vi.fn(() => Promise.resolve())
  confetti.create = vi.fn(() => confetti)
  confetti.reset = vi.fn()
  confetti.shapeFromText = vi.fn(({ text }) => ({ type: 'bitmap', bitmap: { text, close: vi.fn() } }))
  confetti.shapeFromPath = vi.fn(({ path }) => ({ type: 'path', path }))

  return { default: confetti }
})

const { fire } = await import('../../resources/js/core/fire.js')
const { ERROR_EVENT } = await import('../../resources/js/core/errors.js')
const confetti = (await import('canvas-confetti')).default

describe('fire', () => {
  beforeEach(() => {
    confetti.mockClear()
    confetti.mockImplementation(() => Promise.resolve())
  })

  it('merges the boot defaults underneath the burst options', () => {
    fire({ spread: 360 }, { cannon: confetti, defaults: { spread: 70, ticks: 200 }, policy: 'ignore' })

    expect(confetti).toHaveBeenCalledWith(expect.objectContaining({ spread: 360, ticks: 200 }))
  })

  it('reports a synchronous failure instead of swallowing it', () => {
    // This is where the real failures land: an unbuildable shape, a canvas
    // already owned by another instance. The previous implementation attached
    // a no-op .catch and never saw any of them.
    const error = vi.spyOn(console, 'error').mockImplementation(() => {})
    const listener = vi.fn()
    window.addEventListener(ERROR_EVENT, listener)

    confetti.mockImplementation(() => {
      throw new TypeError('OffscreenCanvas is not defined')
    })

    expect(() => fire({}, { cannon: confetti, policy: 'ignore' })).not.toThrow()
    expect(error).toHaveBeenCalledOnce()
    expect(listener).toHaveBeenCalledOnce()

    window.removeEventListener(ERROR_EVENT, listener)
  })

  it('survives a cannon that returns null instead of a promise', () => {
    // canvas-confetti returns null when there is no global Promise. Calling
    // .catch on that was the second half of the old bug.
    confetti.mockImplementation(() => null)

    expect(() => fire({}, { cannon: confetti, policy: 'ignore' })).not.toThrow()
  })

  it('drops a shape it cannot build rather than failing the burst', () => {
    vi.spyOn(console, 'error').mockImplementation(() => {})
    confetti.shapeFromText.mockImplementationOnce(() => {
      throw new TypeError('OffscreenCanvas is not defined')
    })

    fire({ shapes: [{ type: 'text', text: '🦄' }] }, { cannon: confetti, policy: 'ignore' })

    // The burst still fires; canvas-confetti falls back to its own shapes.
    expect(confetti).toHaveBeenCalledOnce()
    expect(confetti.mock.calls[0][0]).not.toHaveProperty('shapes')
  })
})

describe('reduced motion', () => {
  const preferReducedMotion = (matches) => {
    window.matchMedia = vi.fn(() => ({ matches, addEventListener: vi.fn(), removeEventListener: vi.fn() }))
  }

  beforeEach(() => {
    confetti.mockClear()
    confetti.mockImplementation(() => Promise.resolve())
  })

  it('draws nothing under the skip policy', () => {
    preferReducedMotion(true)

    fire({ particleCount: 200 }, { cannon: confetti, policy: 'skip' })

    expect(confetti).not.toHaveBeenCalled()
  })

  it('halves the particles and shortens the effect under the reduce policy', () => {
    preferReducedMotion(true)

    fire({ particleCount: 200, ticks: 500 }, { cannon: confetti, policy: 'reduce' })

    expect(confetti).toHaveBeenCalledWith(expect.objectContaining({ particleCount: 100, ticks: 100 }))
  })

  it('leaves the burst alone when no preference is set', () => {
    preferReducedMotion(false)

    fire({ particleCount: 200, ticks: 500 }, { cannon: confetti, policy: 'reduce' })

    expect(confetti).toHaveBeenCalledWith(expect.objectContaining({ particleCount: 200, ticks: 500 }))
  })
})
