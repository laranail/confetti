<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Enums;

use Simtabi\Laranail\Enumerator\Attributes\Description;
use Simtabi\Laranail\Enumerator\Attributes\Help;
use Simtabi\Laranail\Enumerator\Attributes\Label;
use Simtabi\Laranail\Enumerator\Concerns\HasEnumeratorBehavior;
use Simtabi\Laranail\Enumerator\Contracts\Enumerator;

/**
 * How the browser bundle reaches the page.
 *
 * `Route` is the default because it is the only mode that cannot serve a stale
 * bundle: the URL carries a content hash, so upgrading the package changes the
 * URL. Every other mode needs either a publish step, a third-party host, or the
 * consumer's own build.
 */
#[Description('How the confetti browser bundle is delivered to the page.')]
enum AssetMode: string implements Enumerator
{
    use HasEnumeratorBehavior;

    #[Label('Route'), Help('Served by the package from a content-hashed, immutably-cached route. No publish step.')]
    case Route = 'route';

    #[Label('Published'), Help('Served from public/vendor/confetti after vendor:publish. Re-publish on upgrade.')]
    case Published = 'published';

    #[Label('CDN'), Help('Loaded from a third-party host with an optional integrity hash.')]
    case Cdn = 'cdn';

    #[Label('Vite'), Help('Resolved from the application\'s own Vite manifest.')]
    case Vite = 'vite';

    #[Label('Off'), Help('Emit no script tag. The application loads the runtime itself.')]
    case Off = 'off';
}
