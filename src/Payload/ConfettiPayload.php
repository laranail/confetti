<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Payload;

use Illuminate\Support\Str;
use JsonSerializable;
use Simtabi\Laranail\Confetti\Support\Json;

/**
 * The wire envelope: everything a single `shoot()` sends to the browser.
 *
 * ```json
 * {
 *   "v": 1,
 *   "id": "01JQ8...",
 *   "action": "fire",
 *   "bursts": [{ "delay": 0, "options": { "spread": 26 } }],
 *   "animations": [{ "animation": "snow", "duration": 15000, "options": {}, "params": {} }]
 * }
 * ```
 *
 * `v` lets the runtime refuse a payload written by a newer package version
 * rather than misinterpreting it. `id` is a deduplication guard: a page restored
 * from the back/forward cache re-runs its boot script, and without an identifier
 * the runtime cannot tell that from a genuine second effect.
 *
 * `action` is `fire` for normal use; `stop` and `reset` let the server abort a
 * running animation, which matters for effects that last fifteen seconds.
 */
final readonly class ConfettiPayload implements JsonSerializable
{
    public const int VERSION = 1;

    public const string ACTION_FIRE = 'fire';

    public const string ACTION_STOP = 'stop';

    public const string ACTION_RESET = 'reset';

    /**
     * @param list<Burst> $bursts
     * @param list<Animation> $animations
     * @param ?string $reducedMotion Per-effect override of the configured policy.
     */
    public function __construct(
        public array $bursts = [],
        public array $animations = [],
        public string $action = self::ACTION_FIRE,
        public ?string $id = null,
        public ?string $reducedMotion = null,
    ) {}

    /**
     * @param list<Burst> $bursts
     * @param list<Animation> $animations
     */
    public static function make(
        array $bursts = [],
        array $animations = [],
        ?string $id = null,
        ?string $reducedMotion = null,
    ): self {
        return new self($bursts, $animations, self::ACTION_FIRE, $id ?? (string) Str::ulid(), $reducedMotion);
    }

    public static function stop(?string $id = null): self
    {
        return new self([], [], self::ACTION_STOP, $id ?? (string) Str::ulid());
    }

    public static function reset(?string $id = null): self
    {
        return new self([], [], self::ACTION_RESET, $id ?? (string) Str::ulid());
    }

    /**
     * Fold another payload into this one.
     *
     * Two `shoot()` calls in one request should reach the browser as a single
     * effect, not as two payloads racing to occupy the same session key. The
     * later payload's action wins, so a `stop()` after a `fire()` behaves as
     * written.
     */
    public function merge(self $other): self
    {
        return new self(
            bursts: [...$this->bursts, ...$other->bursts],
            animations: [...$this->animations, ...$other->animations],
            action: $other->action,
            id: $other->id ?? $this->id,
            reducedMotion: $other->reducedMotion ?? $this->reducedMotion,
        );
    }

    public function isEmpty(): bool
    {
        return $this->bursts === []
            && $this->animations === []
            && $this->action === self::ACTION_FIRE;
    }

    public function burstCount(): int
    {
        return count($this->bursts);
    }

    public function animationCount(): int
    {
        return count($this->animations);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $payload = [
            'v' => self::VERSION,
            'id' => $this->id,
            'action' => $this->action,
            'bursts' => array_map(static fn (Burst $b): array => $b->toArray(), $this->bursts),
            'animations' => array_map(static fn (Animation $a): array => $a->toArray(), $this->animations),
        ];

        if ($this->reducedMotion !== null) {
            $payload['reducedMotion'] = $this->reducedMotion;
        }

        return $payload;
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var list<array{delay?: int, options?: array<string, mixed>}> $bursts */
        $bursts = is_array($data['bursts'] ?? null) ? $data['bursts'] : [];

        /** @var list<array{animation: string}> $animations */
        $animations = is_array($data['animations'] ?? null) ? $data['animations'] : [];

        return new self(
            bursts: array_map(Burst::fromArray(...), array_values($bursts)),
            animations: array_map(Animation::fromArray(...), array_values($animations)),
            action: is_string($data['action'] ?? null) ? $data['action'] : self::ACTION_FIRE,
            id: is_string($data['id'] ?? null) ? $data['id'] : null,
            reducedMotion: is_string($data['reducedMotion'] ?? null) ? $data['reducedMotion'] : null,
        );
    }

    public function toJson(): string
    {
        return Json::encodePlain($this->toArray());
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
