<?php

declare(strict_types=1);

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Simtabi\Laranail\Confetti\Integrations\Filament\ConfettiPlugin;
use Simtabi\Laranail\Confetti\Support\BootConfig;
use Simtabi\Laranail\Confetti\View\ScriptTagBuilder;

/**
 * Filament is a development dependency, so every test here skips when it is
 * absent. That is the CI "minimal" leg, which is what proves the package works
 * on a plain Laravel application.
 */
beforeEach(function (): void {
    if (! interface_exists(Plugin::class)) {
        $this->markTestSkipped('filament/filament is not installed.');
    }
});

/**
 * Read the hooks a panel has collected.
 *
 * A panel only hands them to FilamentView while it boots, and the property is
 * protected until then, so reflection is the way to see what a plugin
 * registered without standing up a whole panel lifecycle.
 *
 * @return array<string, array<string, array<Closure>>>
 */
function panelRenderHooks(Panel $panel): array
{
    $property = new ReflectionProperty(Panel::class, 'renderHooks');

    /** @var array<string, array<string, array<Closure>>> */
    return $property->getValue($panel);
}

function renderPanelHook(Panel $panel, string $hook): string
{
    $hooks = panelRenderHooks($panel);

    return collect($hooks[$hook] ?? [])
        ->flatten()
        ->map(fn (Closure $callback): string => (string) $callback())
        ->implode('');
}

it('defines the plugin exactly when Filament is installed', function (): void {
    // The file-level guard has to leave the class undefined rather than fatal:
    // `implements Plugin` is resolved when PHP compiles the class, so merely
    // autoloading the file without Filament would be a fatal error, and a
    // class_exists() check inside a method could not save it.
    expect(class_exists(ConfettiPlugin::class))
        ->toBe(interface_exists(Plugin::class));
});

it('identifies itself', function (): void {
    expect(ConfettiPlugin::make()->getId())->toBe('laranail-confetti');
});

it('registers a hook at the end of the body', function (): void {
    $panel = Panel::make()->id('testing')->path('admin');

    ConfettiPlugin::make()->register($panel);

    expect(panelRenderHooks($panel))->toHaveKey(PanelsRenderHook::BODY_END);
});

it('renders the same markup a plain Laravel page gets', function (): void {
    // One markup path for every stack: the panel, the Blade component and the
    // middleware all render the same view, so there is one asset pipeline and
    // one runtime rather than a Filament-shaped fork of both.
    $panel = Panel::make()->id('testing-parity')->path('admin');

    ConfettiPlugin::make()->register($panel);

    $expected = view('confetti::components.scripts', [
        'enabled' => true,
        'bootJson' => app(BootConfig::class)->toJson(),
        'scriptTag' => app(ScriptTagBuilder::class)->render(),
    ])->render();

    expect(trim(renderPanelHook($panel, PanelsRenderHook::BODY_END)))
        ->toBe(trim($expected));
});

it('renders nothing when the plugin is disabled', function (): void {
    $panel = Panel::make()->id('testing-disabled')->path('admin');

    ConfettiPlugin::make()->enabled(false)->register($panel);

    expect(renderPanelHook($panel, PanelsRenderHook::BODY_END))->toBe('');
});

it('accepts a different render hook', function (): void {
    // Hook names have shifted between Filament majors, and a panel with an
    // unusual layout may want the tags somewhere else.
    $panel = Panel::make()->id('testing-hook')->path('admin');

    ConfettiPlugin::make()->renderHook(PanelsRenderHook::HEAD_END)->register($panel);

    expect(panelRenderHooks($panel))
        ->toHaveKey(PanelsRenderHook::HEAD_END)
        ->not->toHaveKey(PanelsRenderHook::BODY_END);
});
