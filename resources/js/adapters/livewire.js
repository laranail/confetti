/**
 * Livewire adapter.
 *
 * Livewire's own events already reach the runtime, dispatched on
 * `window`, which is what the runtime listens to, so this exists for
 * navigation, not for firing.
 *
 * `wire:navigate` swaps the page without a load. A fifteen-second snowfall
 * started before that swap has no reason to stop, and would keep falling over
 * whatever the visitor navigated to. So: abort on the way out, re-read the boot
 * block on the way in, since the new page brings its own.
 */
export function registerLivewireAdapter(runtime, target = typeof window !== 'undefined' ? window : null) {
  if (!target || typeof target.addEventListener !== 'function') return runtime

  target.addEventListener('livewire:navigating', () => runtime.stop())
  target.addEventListener('livewire:navigated', () => runtime.refresh())

  return runtime
}
