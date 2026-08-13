import { describe, expect, it, vi } from 'vitest'

import { mergeOptions, normalizePayload } from '../../resources/js/core/normalize.js'

describe('normalizePayload', () => {
  it('reads the current envelope', () => {
    const payload = normalizePayload({
      v: 1,
      id: '01JQ8',
      action: 'fire',
      bursts: [{ delay: 100, options: { spread: 26 } }],
      animations: [{ animation: 'snow', duration: 5000 }],
    })

    expect(payload.id).toBe('01JQ8')
    expect(payload.bursts).toEqual([{ delay: 100, options: { spread: 26 } }])
    expect(payload.animations).toHaveLength(1)
  })

  it('accepts the legacy bare array so a payload flashed before an upgrade still fires', () => {
    const payload = normalizePayload([
      { particleCount: 50, delay: 0 },
      { particleCount: 20, delay: 200 },
    ])

    expect(payload.v).toBe(0)
    expect(payload.bursts).toEqual([
      { delay: 0, options: { particleCount: 50 } },
      { delay: 200, options: { particleCount: 20 } },
    ])
  })

  it('refuses a payload from a newer package rather than guessing at it', () => {
    const error = vi.spyOn(console, 'error').mockImplementation(() => {})

    expect(normalizePayload({ v: 99, bursts: [] })).toBeNull()
    expect(error).toHaveBeenCalledOnce()
  })

  it('returns null for nothing at all', () => {
    expect(normalizePayload(null)).toBeNull()
    expect(normalizePayload(undefined)).toBeNull()
    expect(normalizePayload('nonsense')).toBeNull()
  })
})

describe('mergeOptions', () => {
  it('lets a burst override the defaults', () => {
    expect(mergeOptions({ spread: 70, ticks: 200 }, { spread: 360 })).toEqual({
      spread: 360,
      ticks: 200,
    })
  })

  it('strips delay, which schedules the call rather than configuring it', () => {
    expect(mergeOptions({}, { delay: 500, spread: 26 })).toEqual({ spread: 26 })
  })
})
