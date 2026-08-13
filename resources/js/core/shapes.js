import confetti from 'canvas-confetti'

import { report, warnOnce } from './errors.js'

/**
 * Turns shape descriptors from the payload into the objects canvas-confetti
 * draws, and caches the results.
 *
 * The cache is not an optimisation so much as a correctness measure for memory.
 * `shapeFromText` rasterises an `ImageBitmap`, and a fifteen-second emoji
 * animation calls it on every frame; without caching that is hundreds of
 * bitmaps held until garbage collection catches up. `shapeFromPath` is worse in
 * a different way: given no matrix it works one out by sampling a 1000x1000
 * grid with `isPointInPath`, on the main thread.
 *
 * A `Map` preserves insertion order, so evicting the oldest entry is just
 * taking the first key. Evicted bitmaps are closed explicitly rather than left
 * for the collector.
 */

const cache = new Map()

let maxSize = 32

export function configureShapeCache({ size } = {}) {
  if (typeof size === 'number' && size > 0) maxSize = size
}

function keyFor(descriptor) {
  if (descriptor.type === 'text') {
    return `t|${descriptor.text}|${descriptor.scalar ?? ''}|${descriptor.color ?? ''}|${descriptor.fontFamily ?? ''}`
  }

  return `p|${descriptor.path}|${Array.isArray(descriptor.matrix) ? descriptor.matrix.join(',') : 'auto'}`
}

function remember(key, shape) {
  if (cache.size >= maxSize) {
    const oldest = cache.keys().next().value
    const evicted = cache.get(oldest)

    // Bitmaps hold real memory; the collector will not reclaim it promptly.
    if (evicted && evicted.bitmap && typeof evicted.bitmap.close === 'function') {
      try {
        evicted.bitmap.close()
      } catch {
        // Already closed, or a browser without the method.
      }
    }

    cache.delete(oldest)
  }

  cache.set(key, shape)

  return shape
}

/**
 * Resolve one shape entry.
 *
 * Built-ins arrive as plain strings and pass straight through. A descriptor
 * that cannot be built (no OffscreenCanvas, no Path2D) is reported and
 * dropped, so the burst falls back to the default shapes rather than failing
 * outright.
 */
function resolveOne(entry) {
  if (typeof entry === 'string') return entry

  if (!entry || typeof entry !== 'object') return null

  const key = keyFor(entry)

  if (cache.has(key)) return cache.get(key)

  try {
    if (entry.type === 'text') {
      return remember(
        key,
        confetti.shapeFromText({
          text: entry.text,
          scalar: entry.scalar ?? 1,
          color: entry.color ?? '#000000',
          ...(entry.fontFamily ? { fontFamily: entry.fontFamily } : {}),
        }),
      )
    }

    if (entry.type === 'path') {
      if (!Array.isArray(entry.matrix)) {
        warnOnce(
          `A path shape was sent without a transform matrix. canvas-confetti will derive one by ` +
            `sampling a 1000x1000 grid, on the main thread. Compute it once and pass it to ` +
            `shapeFromPath() for anything that fires often.`,
        )
      }

      return remember(
        key,
        confetti.shapeFromPath(
          Array.isArray(entry.matrix)
            ? { path: entry.path, matrix: entry.matrix }
            : { path: entry.path },
        ),
      )
    }
  } catch (error) {
    report(error, { phase: 'shape', descriptor: entry })

    return null
  }

  return null
}

/**
 * Resolve a burst's `shapes` array.
 *
 * Returns undefined when nothing survived, which lets canvas-confetti fall back
 * to its own defaults instead of drawing an empty list.
 */
export function resolveShapes(shapes) {
  if (!Array.isArray(shapes)) return undefined

  const resolved = shapes.map(resolveOne).filter((shape) => shape !== null && shape !== undefined)

  return resolved.length > 0 ? resolved : undefined
}

/** Release every cached bitmap. Exposed mainly for tests. */
export function clearShapeCache() {
  for (const shape of cache.values()) {
    if (shape && shape.bitmap && typeof shape.bitmap.close === 'function') {
      try {
        shape.bitmap.close()
      } catch {
        // ignore
      }
    }
  }

  cache.clear()
}

export function shapeCacheSize() {
  return cache.size
}
