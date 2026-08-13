<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Exceptions;

use Throwable;

/**
 * Implemented by every exception this package throws.
 *
 * It is a marker interface rather than a base class so each concrete exception
 * can still extend the SPL type that describes it — bad input is an
 * `InvalidArgumentException`, a missing bundle is a `RuntimeException` — while
 * callers who only care that "confetti failed" can catch the family:
 *
 *     try {
 *         Confetti::colors($fromUser)->shoot();
 *     } catch (ConfettiException) {
 *         // decorative; never worth breaking the request over
 *     }
 */
interface ConfettiException extends Throwable {}
