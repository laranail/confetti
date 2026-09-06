<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Commands;

use Simtabi\Laranail\Confetti\Doctor\Checks;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorReporter;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

/**
 * `laranail::confetti.doctor` reports the configuration that decides whether
 * confetti actually appears.
 */
final class DoctorCommand extends Command
{
    // Symfony rejects the empty segment in `::` when validating a command name.
    // The trait writes the name past that check; dispatch still works because
    // an exact match is resolved before the `:`-splitting namespace lookup.
    use SupportsNamespacedNames;

    protected $description = 'Check the confetti setup: bundle, asset delivery, canvas, and detected stacks';

    protected $signature = 'laranail::confetti.doctor {--json : Emit the report as JSON}';

    public function handle(Checks $checks): int
    {
        return DoctorReporter::render($this, $checks->all(), (bool) $this->option('json'));
    }
}
