<?php

declare(strict_types=1);

use Simtabi\Laranail\Confetti\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

/**
 * Compile Blade from scratch once per run.
 *
 * Blade recompiles a template when the .blade.php changes, and a component's
 * resolution to a class is baked into that compiled output. Nothing about it
 * depends on the provider, so changing which class a component tag resolves to
 * leaves every cached template still calling the old one. Locally that means a
 * broken registration keeps passing until something happens to touch the view.
 *
 * This cost real time: a mutation test on the component namespace reported the
 * registration was not load-bearing, because the assertion ran against a
 * template compiled while it still was. CI never sees this, since its cache
 * starts empty, which is exactly what makes it a local-only trap.
 */
$compiled = __DIR__ . '/../vendor/orchestra/testbench-core/laravel/storage/framework/views';

if (is_dir($compiled)) {
    foreach (glob($compiled . '/*.php') ?: [] as $template) {
        @unlink($template);
    }
}

// tests/Isolation deliberately opts in per file, because each one boots the package
// under a different environment, so they cannot share a single base case.

/**
 * Read one option out of the first burst of a payload array.
 *
 * Payloads carry only what differs from the defaults, so a missing key means
 * "the default applies" rather than "unset".
 */
function burstOption(array $payload, string $key, int $burst = 0): mixed
{
    return $payload['bursts'][$burst]['options'][$key] ?? null;
}

/** Read one option out of the first animation descriptor. */
function animationOption(array $payload, string $key, int $index = 0): mixed
{
    return $payload['animations'][$index]['options'][$key] ?? null;
}

/** Read one loop parameter out of the first animation descriptor. */
function animationParam(array $payload, string $key, int $index = 0): mixed
{
    return $payload['animations'][$index]['params'][$key] ?? null;
}
