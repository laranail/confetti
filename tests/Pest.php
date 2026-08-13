<?php

declare(strict_types=1);

use Simtabi\Laranail\Confetti\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

// tests/Isolation deliberately opts in per file — each one boots the package
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
