<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Commands;

use Simtabi\Laranail\Confetti\Confetti;
use Simtabi\Laranail\Confetti\Enums\ConfettiPreset;
use Simtabi\Laranail\Confetti\Exceptions\ConfettiException;
use Simtabi\Laranail\Confetti\Support\Json;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

/**
 * `laranail::confetti.demo` — prints the payload a preset produces.
 *
 * Mostly a way to see the cost of an effect before shipping it. The contrast is
 * the point: `snow` is a couple of hundred bytes as a descriptor and a couple
 * of hundred kilobytes expanded, and the command shows both.
 */
final class DemoCommand extends Command
{
    use SupportsNamespacedNames;

    protected $signature = 'laranail::confetti.demo
                            {preset? : The preset to inspect; omit to list them all}
                            {--expand : Expand continuous effects into bursts, as expand() does}
                            {--duration= : Duration in milliseconds, for a continuous effect}
                            {--json : Print the raw payload}';

    protected $description = 'Print the payload a confetti preset produces, and what it costs on the wire';

    /** @var list<string> */
    protected array $commandAliases = ['confetti:demo'];

    public function handle(Confetti $confetti): int
    {
        $preset = $this->argument('preset');

        if (! is_string($preset) || $preset === '') {
            return $this->listPresets($confetti);
        }

        if (! $confetti->presets()->has($preset)) {
            $this->error("Unknown preset '{$preset}'.");
            $this->line('  Available: '.implode(', ', $confetti->presets()->names()));

            return self::FAILURE;
        }

        $duration = $this->option('duration');
        $args = is_numeric($duration) ? [(int) $duration] : [];

        $builder = $confetti->make()->preset($preset, ...$args);

        if ($this->option('expand')) {
            $builder->expand();
        }

        try {
            $payload = $builder->toPayload();
        } catch (ConfettiException $e) {
            // Expanding a full-length continuous effect exceeds the burst
            // ceiling by design — the exception explains why, and a stack trace
            // adds nothing to it.
            $this->newLine();
            $this->line('  <fg=red>'.$e->getMessage().'</>');
            $this->newLine();
            $this->line('  Try a shorter duration, for example:');
            $this->line("      <fg=cyan>laranail::confetti.demo {$preset} --expand --duration=3000</>");
            $this->newLine();

            return self::FAILURE;
        }

        $json = Json::encodePlain($payload->toArray());

        if ($this->option('json')) {
            $this->line($json);

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line(sprintf('  <options=bold>%s</>', $preset));
        $this->newLine();
        $this->line(sprintf('    Bursts      %d', $payload->burstCount()));
        $this->line(sprintf('    Animations  %d', $payload->animationCount()));
        $this->line(sprintf('    Wire size   %s', $this->humanBytes(strlen($json))));
        $this->newLine();

        if ($payload->animationCount() > 0 && ! $this->option('expand')) {
            $this->line('    <fg=gray>Runs as an animation loop in the browser. Add --expand to see the</>');
            $this->line('    <fg=gray>bursts it would take to do the same thing from PHP.</>');
            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function listPresets(Confetti $confetti): int
    {
        $this->newLine();
        $this->line('  <options=bold>Presets</>');
        $this->newLine();

        foreach ($confetti->presets()->names() as $name) {
            $case = ConfettiPreset::tryFrom($name);

            $this->line(sprintf(
                '    <fg=cyan>%-14s</> %s%s',
                $name,
                $case?->kind() ?? 'custom',
                $case?->isOfficial() ? ' <fg=gray>(canvas-confetti recipe)</>' : '',
            ));
        }

        $this->newLine();
        $this->line('  Inspect one with <options=bold>laranail::confetti.demo <preset></>.');
        $this->newLine();

        return self::SUCCESS;
    }

    private function humanBytes(int $bytes): string
    {
        return $bytes < 1024 ? $bytes.' B' : round($bytes / 1024, 1).' KB';
    }
}
