<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Integrations\Filament\Providers;

use Illuminate\Support\ServiceProvider;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\View\ConfettiTags;
use Throwable;

/**
 * Powers the opt-in `integrations.filament.auto` mode.
 *
 * Registered unconditionally by the main provider and gates itself, which is
 * the counterpart to the guard on {@see ConfettiPlugin}. The two solve
 * different problems and neither replaces the other: a conditionally-defined
 * class cannot be listed in `extra.laravel.providers`, because Laravel would
 * try to instantiate something that may not exist; and a self-gating provider
 * cannot rescue a class whose `implements` clause fails at compile time.
 *
 * With `auto` enabled every panel gets the confetti tags without a
 * `PanelProvider` edit. It is off by default; registering the plugin by hand
 * is clearer, and a package silently modifying every panel in an application is
 * the kind of helpfulness that becomes hard to trace.
 *
 * Filament is referenced by string throughout, so this file loads cleanly when
 * Filament is absent.
 */
final class FilamentServiceProvider extends ServiceProvider
{
    private const string FACADE = 'Filament\\Facades\\Filament';

    private const string PLUGIN_CONTRACT = 'Filament\\Contracts\\Plugin';

    private const string BODY_END = 'panels::body.end';

    public function boot(): void
    {
        if (! $this->shouldRegister()) {
            return;
        }

        try {
            // The array form is required, not stylistic: self::FACADE is a
            // class that may not exist, so the call has to stay dynamic. A
            // first-class callable would bind to this class instead.
            call_user_func([self::FACADE, 'serving'], function (): void {
                $this->renderIntoCurrentPanel();
            });
        } catch (Throwable) {
            // Filament is installed but not shaped the way we expect. Panels
            // simply do not get confetti automatically; nothing else breaks.
        }
    }

    private function renderIntoCurrentPanel(): void
    {
        try {
            $panel = call_user_func([self::FACADE, 'getCurrentPanel']);

            if (! is_object($panel) || ! is_callable($panel->renderHook(...))) {
                return;
            }

            $config = $this->app->make(ConfettiConfig::class);
            $hook = $config->integration('filament')['hook'] ?? null;

            $panel->renderHook(
                is_string($hook) && $hook !== '' ? $hook : self::BODY_END,
                fn (): string => $this->app->make(ConfettiTags::class)->render('filament-auto'),
            );
        } catch (Throwable) {
            //
        }
    }

    private function shouldRegister(): bool
    {
        if (! class_exists(self::FACADE) || ! interface_exists(self::PLUGIN_CONTRACT)) {
            return false;
        }

        if (! $this->app->bound(ConfettiConfig::class)) {
            return false;
        }

        $config = $this->app->make(ConfettiConfig::class);

        return $config->enabled
            && $config->integrationEnabled('filament')
            && (bool) ($config->integration('filament')['auto'] ?? false);
    }
}
