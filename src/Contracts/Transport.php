<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Contracts;

use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;

/**
 * Carries a payload from PHP to the browser.
 *
 * Add your own with `Confetti::extend('broadcast', fn ($app) => new MyTransport)`
 * — a websocket transport for firing confetti at other people's screens is the
 * obvious case the built-in drivers do not cover.
 */
interface Transport
{
    /** The driver name, as used in configuration and `via()`. */
    public function name(): string;

    /**
     * Whether this transport can run right now.
     *
     * Consulted during automatic resolution, so it must be cheap and must not
     * throw. A transport that needs a package which may not be installed should
     * check with `class_exists()` rather than importing the class.
     */
    public function available(): bool;

    public function send(ConfettiPayload $payload): void;
}
