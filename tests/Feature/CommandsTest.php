<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;

it('reports a healthy setup', function (): void {
    $this->artisan('laranail::confetti.doctor')
        ->expectsOutputToContain('Browser bundle')
        ->expectsOutputToContain('Asset delivery')
        ->assertSuccessful();
});

it('fails when the asset mode cannot deliver anything', function (): void {
    config()->set('laranail.confetti.assets.mode', 'cdn');
    config()->set('laranail.confetti.assets.cdn_url');
    app()->forgetInstance(ConfettiConfig::class);

    $this->artisan('laranail::confetti.doctor')->assertFailed();
});

it('warns about server-side expansion', function (): void {
    config()->set('laranail.confetti.presets.expansion', 'server');
    app()->forgetInstance(ConfettiConfig::class);

    $this->artisan('laranail::confetti.doctor')
        ->expectsOutputToContain('hundreds of kilobytes')
        ->assertSuccessful();
});

it('lists the presets', function (): void {
    $this->artisan('laranail::confetti.demo')
        ->expectsOutputToContain('realistic')
        ->expectsOutputToContain('schoolPride')
        ->assertSuccessful();
});

it('shows what a preset costs on the wire', function (): void {
    $this->artisan('laranail::confetti.demo', ['preset' => 'realistic'])
        ->expectsOutputToContain('Bursts')
        ->expectsOutputToContain('Wire size')
        ->assertSuccessful();
});

it('rejects an unknown preset', function (): void {
    $this->artisan('laranail::confetti.demo', ['preset' => 'nope'])->assertFailed();
});

it('expands a continuous effect at a workable duration', function (): void {
    $this->artisan('laranail::confetti.demo', [
        'preset' => 'snow',
        '--expand' => true,
        '--duration' => 3000,
    ])->expectsOutputToContain('Bursts')->assertSuccessful();
});

it('explains the burst ceiling rather than throwing at it', function (): void {
    // Expanding fifteen seconds of snow is five hundred bursts, well past the
    // ceiling. That is the documented behaviour, so it should read as an
    // answer with a way forward, not as an uncaught exception.
    $this->artisan('laranail::confetti.demo', ['preset' => 'snow', '--expand' => true])
        ->expectsOutputToContain('over the configured limit')
        ->expectsOutputToContain('--duration=3000')
        ->assertFailed();
});

it('prints the remaining setup steps on install', function (): void {
    $this->artisan('laranail::confetti.install')
        ->expectsOutputToContain('x-confetti::scripts')
        ->assertSuccessful();
});

it('registers the commands under the laranail namespace', function (): void {
    $commands = array_keys(Artisan::all());

    expect($commands)
        ->toContain('laranail::confetti.doctor')
        ->toContain('laranail::confetti.demo')
        ->toContain('laranail::confetti.install');
});
