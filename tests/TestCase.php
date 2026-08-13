<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Providers\ConfettiServiceProvider;

/**
 * The default test case.
 *
 * Registers the optional integrations' providers only when those packages are
 * installed, so the whole suite runs on the "minimal" CI leg with Livewire,
 * Filament and Inertia removed. Tests that genuinely need one guard themselves
 * with a skip.
 */
abstract class TestCase extends Orchestra
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        $providers = [ConfettiServiceProvider::class];

        foreach ([
            'Livewire\\LivewireServiceProvider',
            'Filament\\FilamentServiceProvider',
            'Filament\\Support\\SupportServiceProvider',
            'Filament\\Actions\\ActionsServiceProvider',
            'Filament\\Schemas\\SchemasServiceProvider',
            'Filament\\Tables\\TablesServiceProvider',
            'BladeUI\\Icons\\BladeIconsServiceProvider',
        ] as $provider) {
            if (class_exists($provider)) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    /** @return array<string, class-string> */
    protected function getPackageAliases($app): array
    {
        return [
            'Confetti' => Confetti::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.url', 'http://localhost');
        $app['config']->set('app.key', 'base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('cache.default', 'array');
    }
}
