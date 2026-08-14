<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Commands;

use Simtabi\Laranail\Confetti\Doctor\Checks;
use Simtabi\Laranail\Console\Tools\Commands\Command;
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

    protected $signature = 'laranail::confetti.doctor';

    protected $description = 'Check the confetti setup: bundle, asset delivery, canvas, and detected stacks';

    public function handle(Checks $checks): int
    {
        $results = $checks->all();
        $failed = false;

        $this->newLine();
        $this->line('  <options=bold>laranail/confetti</>');
        $this->newLine();

        foreach ($results as $result) {
            [$icon, $style] = match ($result['status']) {
                Checks::FAIL => ['✗', 'fg=red'],
                Checks::WARN => ['!', 'fg=yellow'],
                default => ['✓', 'fg=green'],
            };

            $failed = $failed || $result['status'] === Checks::FAIL;

            $this->line(sprintf('  <%s>%s</> <options=bold>%s</>', $style, $icon, $result['name']));
            $this->line('    '.$result['message']);
            $this->newLine();
        }

        if ($failed) {
            $this->line('  <fg=red>Something above will stop confetti from appearing.</>');
            $this->newLine();

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
