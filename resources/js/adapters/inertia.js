/**
 * Inertia adapter.
 *
 * An Inertia visit returns JSON, so there is no boot block to re-read and no
 * session flash to carry the payload. It arrives as a page prop instead, and is
 * picked up here on `inertia:success`.
 *
 * Running animations are aborted before each visit for the same reason as the
 * Livewire adapter — an effect belongs to the page that started it.
 */
export function registerInertiaAdapter(
  runtime,
  { prop = 'confetti', target = typeof window !== 'undefined' ? window : null } = {},
) {
  if (!target || typeof target.addEventListener !== 'function') return runtime

  target.addEventListener('inertia:before', () => runtime.stop())

  target.addEventListener('inertia:success', (event) => {
    const page = event?.detail?.page

    const payload = page?.props?.[prop]

    if (payload) runtime.fire(payload)
  })

  return runtime
}
