import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('canvas-confetti', () => {
  const confetti = vi.fn(() => Promise.resolve())
  confetti.create = vi.fn(() => confetti)
  confetti.shapeFromText = vi.fn(({ text, scalar }) => ({
    type: 'bitmap',
    bitmap: { text, scalar, close: vi.fn() },
  }))
  confetti.shapeFromPath = vi.fn(({ path, matrix }) => ({ type: 'path', path, matrix }))

  return { default: confetti }
})

const { clearShapeCache, configureShapeCache, resolveShapes, shapeCacheSize } = await import(
  '../../resources/js/core/shapes.js'
)
const confetti = (await import('canvas-confetti')).default

describe('shape cache', () => {
  beforeEach(() => {
    clearShapeCache()
    configureShapeCache({ size: 32 })
    confetti.shapeFromText.mockClear()
    confetti.shapeFromPath.mockClear()
  })

  it('passes built-in shape names straight through', () => {
    expect(resolveShapes(['square', 'circle', 'star'])).toEqual(['square', 'circle', 'star'])
    expect(confetti.shapeFromText).not.toHaveBeenCalled()
  })

  it('rasterises a text shape once and reuses the same object', () => {
    // Without this, a fifteen-second emoji animation allocates a bitmap per
    // frame and holds every one of them.
    const first = resolveShapes([{ type: 'text', text: '🦄', scalar: 2 }])
    const second = resolveShapes([{ type: 'text', text: '🦄', scalar: 2 }])

    expect(confetti.shapeFromText).toHaveBeenCalledOnce()
    expect(first[0]).toBe(second[0])
  })

  it('treats a different scalar as a different shape', () => {
    resolveShapes([{ type: 'text', text: '🦄', scalar: 2 }])
    resolveShapes([{ type: 'text', text: '🦄', scalar: 1 }])

    expect(confetti.shapeFromText).toHaveBeenCalledTimes(2)
  })

  it('caches a path shape by its path and matrix', () => {
    const matrix = [1, 0, 0, 1, 0, 0]

    const first = resolveShapes([{ type: 'path', path: 'M0 0 L10 10z', matrix }])
    const second = resolveShapes([{ type: 'path', path: 'M0 0 L10 10z', matrix }])

    expect(confetti.shapeFromPath).toHaveBeenCalledOnce()
    expect(first[0]).toBe(second[0])
  })

  it('warns once when a path arrives without a matrix', () => {
    // Deriving one means sampling a 1000x1000 grid on the main thread.
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {})

    resolveShapes([{ type: 'path', path: 'M0 0 L1 1z' }])
    resolveShapes([{ type: 'path', path: 'M2 2 L3 3z' }])

    expect(warn).toHaveBeenCalledOnce()
  })

  it('closes the bitmap it evicts rather than leaving it to the collector', () => {
    configureShapeCache({ size: 2 })

    const first = resolveShapes([{ type: 'text', text: 'a' }])[0]
    resolveShapes([{ type: 'text', text: 'b' }])
    resolveShapes([{ type: 'text', text: 'c' }])

    expect(first.bitmap.close).toHaveBeenCalledOnce()
    expect(shapeCacheSize()).toBe(2)
  })

  it('returns undefined when nothing survived, so canvas-confetti uses its own defaults', () => {
    vi.spyOn(console, 'error').mockImplementation(() => {})
    confetti.shapeFromText.mockImplementationOnce(() => {
      throw new TypeError('OffscreenCanvas is not defined')
    })

    expect(resolveShapes([{ type: 'text', text: '🦄' }])).toBeUndefined()
  })
})
