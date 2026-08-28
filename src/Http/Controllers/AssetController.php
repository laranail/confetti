<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Simtabi\Laranail\Confetti\Support\Assets;

/**
 * Serves the browser bundle straight from the package directory.
 *
 * The filename is matched against a fixed map rather than joined onto a path,
 * so there is no traversal surface even before the route constraint.
 *
 * Responses are immutable and cached for a year, which is safe because the URL
 * carries the bundle's content hash: a new version is a new URL. The ETag lets
 * a client that ignores the hash revalidate cheaply.
 *
 * A missing bundle returns 404 with an explanatory comment rather than a 500.
 * Confetti is decorative; it should never be the reason a page fails.
 */
final class AssetController
{
    public function __invoke(Request $request, Assets $assets, string $file = Assets::IIFE): Response
    {
        if (! Assets::isKnown($file)) {
            return $this->missing("laranail/confetti: unknown asset '{$file}'.");
        }

        if (! $assets->exists($file)) {
            return $this->missing(
                'laranail/confetti: the browser bundle has not been built. '
                . 'Run `npm install && npm run build` in the package directory.',
            );
        }

        $response = new Response($assets->contents($file), 200, [
            'Content-Type'           => $assets->contentType($file),
            'Cache-Control'          => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->setEtag($assets->hash($file));
        $response->isNotModified($request);

        return $response;
    }

    private function missing(string $message): Response
    {
        // A JavaScript comment, so a browser that loads this as a script sees
        // the explanation in the network panel rather than a parse error.
        return new Response("// {$message}\n", 404, [
            'Content-Type'  => 'application/javascript',
            'Cache-Control' => 'no-store',
        ]);
    }
}
