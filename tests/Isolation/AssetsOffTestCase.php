<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Tests\Isolation;

use Simtabi\Laranail\Confetti\Tests\TestCase;

/**
 * An application that has switched asset delivery away from the route mode.
 *
 * The route is registered while the provider boots, so the setting has to be in
 * place before that — which a config call inside a test body is too late for.
 */
abstract class AssetsOffTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('laranail.confetti.assets.mode', 'off');
    }
}
