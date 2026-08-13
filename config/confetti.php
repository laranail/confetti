<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | When false, shoot() becomes a no-op. Everything still builds and can be
    | inspected with toArray(), so tests keep working. Nothing is sent.
    |
    */

    'enabled' => env('CONFETTI_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Event names
    |--------------------------------------------------------------------------
    |
    | The browser event the runtime listens for. The legacy name exists so a
    | payload flashed before an upgrade still fires after it; set it to null
    | once you are past that window.
    |
    */

    'event' => env('CONFETTI_EVENT', 'confetti:fire'),

    'legacy_event' => 'fire-confetti',

    /*
    |--------------------------------------------------------------------------
    | Transport
    |--------------------------------------------------------------------------
    |
    | How a payload reaches the browser. "auto" picks per request (Livewire,
    | then Inertia, then a session flash) and falls back to discarding the
    | payload outside HTTP, so firing confetti from a queued job is harmless
    | rather than fatal.
    |
    | Supported: "auto", "session", "livewire", "inertia", "null", "array"
    |
    */

    'transport' => [
        'driver' => env('CONFETTI_TRANSPORT', 'auto'),
        'session_key' => env('CONFETTI_SESSION_KEY', 'laranail.confetti'),
        'inertia_prop' => env('CONFETTI_INERTIA_PROP', 'confetti'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default options
    |--------------------------------------------------------------------------
    |
    | Applied to every burst unless a preset or a builder call overrides them.
    | These travel to the browser once, in the boot payload, and individual
    | bursts carry only what differs, which is why a five-burst effect is a few
    | hundred bytes rather than a few thousand.
    |
    | Every key here is a canvas-confetti option. Note that "gravity" is tripled
    | internally by the library, so 1 is a brisk fall rather than a gentle one.
    |
    */

    'defaults' => [
        'particleCount' => env('CONFETTI_PARTICLE_COUNT', 150),
        'spread' => 70,
        'ticks' => 200,
        'shapes' => ['square', 'circle'],
        'colors' => [
            '#26ccff', '#a25afd', '#ff5e7e', '#88ff5a', '#fcff42', '#ffa62d', '#ff36ff',
        ],
        'zIndex' => 100,
        'disableForReducedMotion' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Named palettes
    |--------------------------------------------------------------------------
    |
    | Reach for one with palette('gold'). Colours must be hex, because canvas-confetti
    | parses a colour by discarding non-hex characters, so a CSS colour name
    | does not fail, it just paints something arbitrary.
    |
    | A null palette means "use the default colours above".
    |
    */

    'palettes' => [
        'default' => null,
        'success' => ['#00ff00', '#32cd32', '#00ef10', '#adff2f', '#7cfc00'],
        'magic' => ['#a25afd', '#ff36ff', '#26ccff', '#ffffff'],
        'gold' => ['#ffe400', '#ffbd00', '#e89400', '#ffca6c', '#fdffb8'],
        'snow' => ['#ffffff'],
        'pride' => ['#bb0000', '#ffffff'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Presets
    |--------------------------------------------------------------------------
    |
    | "expansion" decides where the continuous effects (fireworks, snow,
    | schoolPride) are turned into individual bursts.
    |
    |   client  Send a compact descriptor and let the browser run the loop.
    |           Roughly 250 bytes, and each visitor gets their own randomness.
    |
    |   server  Walk the loop in PHP and ship every burst. Snow at the default
    |           duration is around five hundred of them, so expect a payload in
    |           the hundreds of kilobytes and identical "random" snow for
    |           everyone. Worth it only to assert on bursts in a test, or if the
    |           application does not load this package's runtime.
    |
    | "seed" fixes the random sequence used by server expansion.
    |
    */

    'presets' => [
        'expansion' => env('CONFETTI_PRESET_EXPANSION', 'client'),
        'duration' => env('CONFETTI_PRESET_DURATION', 15000),
        'seed' => env('CONFETTI_PRESET_SEED'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Named effects
    |--------------------------------------------------------------------------
    |
    | Your own confetti configurations, named so the call site says what it
    | means rather than how it looks:
    |
    |     Confetti::effect('checkout')->shoot();
    |
    | Each key is a builder method and each value its arguments, so anything the
    | builder can do an effect can declare. A list is spread as separate
    | arguments, which is how origin => [0.5, 0.7] reaches origin(0.5, 0.7).
    |
    | The indirection is the point: what "checkout" looks like becomes a config
    | decision, and the controller keeps saying effect('checkout').
    |
    */

    'effects' => [

        'celebrate' => [
            'preset' => 'realistic',
        ],

        'subtle' => [
            'count' => 40,
            'spread' => 45,
            'position' => 'top',
            'ticks' => 120,
        ],

        'award' => [
            'preset' => 'stars',
            'palette' => 'gold',
        ],

        'party' => [
            'preset' => 'schoolPride',
            'duration' => 6000,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Asset delivery
    |--------------------------------------------------------------------------
    |
    |   route      Served by the package from a content-hashed URL with an
    |              immutable cache header. No publish step, and upgrading the
    |              package changes the URL, so a stale bundle is impossible.
    |
    |   published  Served from public/vendor/confetti after
    |              `vendor:publish --tag=laranail::confetti-assets`. Remember to
    |              re-publish on upgrade.
    |
    |   cdn        Loaded from "cdn_url", with "cdn_integrity" as an SRI hash if
    |              you set one.
    |
    |   vite       Resolved from your own Vite manifest. Falls back to "route"
    |              and logs a warning if the entry is missing.
    |
    |   off        No script tag at all. For applications that bundle the
    |              runtime themselves.
    |
    | "version" overrides the cache-busting string; leave it null to use the
    | bundle's own content hash.
    |
    */

    'assets' => [
        'mode' => env('CONFETTI_ASSETS', 'route'),
        'route' => env('CONFETTI_ASSETS_ROUTE', '/vendor/confetti'),
        'middleware' => [],
        'cdn_url' => env('CONFETTI_CDN_URL'),
        'cdn_integrity' => env('CONFETTI_CDN_INTEGRITY'),
        'vite_entry' => env('CONFETTI_VITE_ENTRY', 'resources/js/confetti.js'),
        'version' => env('CONFETTI_ASSETS_VERSION'),
        'defer' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic injection
    |--------------------------------------------------------------------------
    |
    | With "auto" enabled, a middleware appends the confetti tags before the
    | closing </body> of every HTML response, so no layout changes are needed.
    | It is off by default because silently rewriting responses should be a
    | decision, not a surprise. Place <x-confetti::scripts /> yourself if you
    | would rather be explicit.
    |
    | The middleware skips redirects, streamed responses, non-HTML content
    | types, Inertia and Livewire responses, and anything matching "except". It
    | also skips a response that already carries the tags, so the component and
    | the middleware can coexist.
    |
    */

    'inject' => [
        'auto' => env('CONFETTI_AUTO_INJECT', false),
        'group' => env('CONFETTI_INJECT_GROUP', 'web'),
        'only' => [],
        'except' => [
            'telescope*',
            'horizon*',
            '_debugbar*',
            'livewire/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | A nonce to place on the script tag under a strict Content-Security-Policy.
    | The boot payload needs no nonce; it is a JSON data block, which the
    | browser never executes.
    |
    */

    'security' => [
        'csp_nonce' => env('CONFETTI_CSP_NONCE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Browser runtime
    |--------------------------------------------------------------------------
    |
    | "use_worker" renders on a worker thread, which keeps a heavy effect off
    | the main thread. It builds the worker from a blob URL, so a strict CSP
    | needs `worker-src blob:`; without it canvas-confetti logs and falls back
    | to the main thread on its own.
    |
    | "canvas" is a CSS selector for your own canvas element. Note that
    | canvas-confetti ignores zIndex on a canvas it did not create, so the
    | runtime applies the positioning and stacking itself in that case.
    |
    | "reduced_motion" is checked before every fire:
    |   ignore  Fire everything as normal.
    |   reduce  Collapse animations to one short, half-count burst.
    |   skip    Draw nothing.
    |
    */

    'runtime' => [
        'use_worker' => env('CONFETTI_USE_WORKER', true),
        'canvas' => env('CONFETTI_CANVAS'),
        'reduced_motion' => env('CONFETTI_REDUCED_MOTION', 'reduce'),
        'pause_when_hidden' => true,
        'max_concurrent_animations' => 3,
        'shape_cache_size' => 32,
        'debug' => env('CONFETTI_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    |
    | Ceilings on anything that could be threaded through from user input.
    | canvas-confetti has no limits of its own; it will accept a million
    | particles and then spend the frame budget on them.
    |
    */

    'limits' => [
        'max_particles' => 1000,
        'max_ticks' => 2000,
        'max_delay' => 60000,
        'max_duration' => 60000,
        'max_bursts' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Strict mode raises on an out-of-range option. With it off, values are
    | clamped and logged instead, which is appropriate if confetti is driven by data you
    | do not control and a decorative effect must never break a page, but it
    | does mean staging and production can differ silently.
    |
    */

    'validation' => [
        'strict' => env('CONFETTI_STRICT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrations
    |--------------------------------------------------------------------------
    |
    | Each one is inert unless its package is installed, so leaving them enabled
    | costs nothing.
    |
    | The Filament plugin is registered on a panel by hand. Set "auto" to true
    | to apply it to every panel instead, without touching a PanelProvider.
    |
    | Inertia is off by default: sharing a page prop only helps if the client
    | adapter is loaded, so it should be a deliberate choice.
    |
    */

    'integrations' => [

        'filament' => [
            'enabled' => env('CONFETTI_FILAMENT', true),
            'auto' => false,
            'hook' => null,
        ],

        'livewire' => [
            'enabled' => true,
        ],

        'inertia' => [
            'enabled' => env('CONFETTI_INERTIA', false),
        ],

    ],

];
