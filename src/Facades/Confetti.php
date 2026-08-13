<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Facades;

use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\Confetti\Builder\ConfettiBuilder;
use Simtabi\Laranail\Confetti\Confetti as ConfettiService;
use Simtabi\Laranail\Confetti\Contracts\Shape;
use Simtabi\Laranail\Confetti\Enums\ConfettiAnimation;
use Simtabi\Laranail\Confetti\Enums\ConfettiPosition;
use Simtabi\Laranail\Confetti\Enums\ConfettiPreset;
use Simtabi\Laranail\Confetti\Enums\ConfettiShape;
use Simtabi\Laranail\Confetti\Enums\ReducedMotionPolicy;
use Simtabi\Laranail\Confetti\Enums\TransportDriver;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;
use Simtabi\Laranail\Confetti\Presets\PresetRegistry;
use Simtabi\Laranail\Confetti\Support\ConfettiConfig;
use Simtabi\Laranail\Confetti\Testing\ConfettiFake;
use Simtabi\Laranail\Confetti\Transport\TransportManager;

/**
 * @method static ConfettiBuilder make()
 * @method static void stop()
 * @method static void flush()
 * @method static ConfettiFake fake()
 * @method static void restore()
 * @method static ConfettiService registerPreset(string $name, callable $factory)
 * @method static ConfettiService extend(string $driver, \Closure $factory)
 * @method static ConfettiConfig config()
 * @method static PresetRegistry presets()
 * @method static TransportManager transports()
 *
 * Particles
 * @method static ConfettiBuilder count(int $count)
 * @method static ConfettiBuilder spread(float|int $spread)
 * @method static ConfettiBuilder angle(float|int $angle)
 * @method static ConfettiBuilder startVelocity(float|int $velocity)
 * @method static ConfettiBuilder decay(float|int $decay)
 * @method static ConfettiBuilder gravity(float|int $gravity)
 * @method static ConfettiBuilder drift(float|int $drift)
 * @method static ConfettiBuilder ticks(int $ticks)
 * @method static ConfettiBuilder scalar(float|int $scalar)
 * @method static ConfettiBuilder flat(bool $flat = true)
 * @method static ConfettiBuilder zIndex(int $zIndex)
 * @method static ConfettiBuilder option(string $key, mixed $value)
 *
 * Position
 * @method static ConfettiBuilder origin(float $x, float $y)
 * @method static ConfettiBuilder originX(float $x)
 * @method static ConfettiBuilder originY(float $y)
 * @method static ConfettiBuilder position(ConfettiPosition|string $position)
 * @method static ConfettiBuilder center()
 * @method static ConfettiBuilder top()
 * @method static ConfettiBuilder bottom()
 * @method static ConfettiBuilder left()
 * @method static ConfettiBuilder right()
 * @method static ConfettiBuilder topLeft()
 * @method static ConfettiBuilder topRight()
 * @method static ConfettiBuilder bottomLeft()
 * @method static ConfettiBuilder bottomRight()
 *
 * Appearance
 * @method static ConfettiBuilder colors(string|array ...$colors)
 * @method static ConfettiBuilder palette(string $name)
 * @method static ConfettiBuilder shapes(ConfettiShape|Shape|string|array ...$shapes)
 * @method static ConfettiBuilder shapeFromPath(string $path, ?array $matrix = null)
 * @method static ConfettiBuilder shapeFromText(string $text, ?float $scalar = null, string $color = '#000000', ?string $fontFamily = null)
 *
 * Timing
 * @method static ConfettiBuilder delay(int $milliseconds)
 * @method static ConfettiBuilder duration(int $milliseconds)
 * @method static ConfettiBuilder stagger(int $milliseconds)
 *
 * Accessibility
 * @method static ConfettiBuilder disableForReducedMotion(bool $disable = true)
 * @method static ConfettiBuilder reducedMotion(ReducedMotionPolicy|string $policy)
 * @method static ConfettiBuilder skipForReducedMotion()
 *
 * Presets
 * @method static ConfettiBuilder preset(ConfettiPreset|string $preset, mixed ...$args)
 * @method static ConfettiBuilder stars()
 * @method static ConfettiBuilder success()
 * @method static ConfettiBuilder magic()
 * @method static ConfettiBuilder rain()
 * @method static ConfettiBuilder realistic()
 * @method static ConfettiBuilder emoji(string $text = '🦄')
 * @method static ConfettiBuilder fireworks(?int $duration = null)
 * @method static ConfettiBuilder snow(?int $duration = null)
 * @method static ConfettiBuilder schoolPride(?int $duration = null)
 *
 * Dispatch
 * @method static ConfettiBuilder then(bool $reset = false)
 * @method static void shoot()
 * @method static ConfettiBuilder via(TransportDriver|string $driver)
 * @method static ConfettiBuilder seed(int $seed)
 * @method static ConfettiBuilder expand(bool $expand = true)
 * @method static ConfettiPayload toPayload()
 * @method static array toArray()
 * @method static array toResolvedArray()
 * @method static string toJson(int $flags = 0)
 *
 * Assertions (available after fake())
 * @method static void assertFired(?callable $callback = null)
 * @method static void assertFiredTimes(int $times)
 * @method static void assertNothingFired()
 * @method static void assertAnimation(ConfettiAnimation|string $animation)
 * @method static void assertBurstCount(int $count)
 *
 * @see ConfettiService
 */
final class Confetti extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ConfettiService::class;
    }

    /**
     * Route the assertion helpers to the active fake.
     *
     * Keeps `Confetti::fake()` followed by `Confetti::assertFired()` reading
     * the way the rest of Laravel's fakes do, without the service itself
     * carrying test-only methods.
     */
    public static function __callStatic($method, $args)
    {
        if (str_starts_with((string) $method, 'assert')) {
            $fake = self::getFacadeRoot()->fakeInstance();

            if ($fake === null) {
                throw new \RuntimeException(
                    'Call Confetti::fake() before asserting on confetti; otherwise payloads are sent, not recorded.'
                );
            }

            return $fake->{$method}(...$args);
        }

        return parent::__callStatic($method, $args);
    }
}
