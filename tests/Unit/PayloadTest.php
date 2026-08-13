<?php

declare(strict_types=1);

use Simtabi\Laranail\Confetti\Exceptions\InvalidOption;
use Simtabi\Laranail\Confetti\Facades\Confetti;
use Simtabi\Laranail\Confetti\Payload\ConfettiPayload;
use Simtabi\Laranail\Confetti\Support\Json;

describe('wire size', function (): void {
    it('sends a continuous effect as a descriptor, not hundreds of bursts', function (): void {
        // Expanded server-side, snow at the default duration is roughly five
        // hundred confetti() calls — about 150KB on every response, with the
        // randomness already decided so every visitor sees identical snow.
        foreach (['snow', 'schoolPride', 'fireworks'] as $preset) {
            $json = Confetti::preset($preset)->toJson();

            expect(strlen($json))->toBeLessThan(600, "{$preset} payload should stay small");
            expect(Confetti::preset($preset)->toPayload()->bursts)->toBe([]);
            expect(Confetti::preset($preset)->toPayload()->animations)->toHaveCount(1);
        }
    });

    it('keeps a multi-burst preset compact by omitting the defaults', function (): void {
        expect(strlen(Confetti::realistic()->toJson()))->toBeLessThan(800);
    });
});

describe('expand()', function (): void {
    it('walks the loop in PHP when asked', function (): void {
        $payload = Confetti::snow(1500)->expand()->toPayload();

        expect($payload->animations)->toBe([]);
        expect($payload->burstCount())->toBeGreaterThan(10);
    });

    it('is deterministic under a seed', function (): void {
        $first = Confetti::snow(900)->seed(1234)->expand()->toArray();
        $second = Confetti::snow(900)->seed(1234)->expand()->toArray();

        // Ignore the per-payload id, which is deliberately unique.
        unset($first['id'], $second['id']);

        expect($first)->toBe($second);
    });

    it('varies with a different seed', function (): void {
        $a = Confetti::snow(900)->seed(1)->expand()->toArray();
        $b = Confetti::snow(900)->seed(2)->expand()->toArray();

        expect($a['bursts'][0]['options']['origin'])->not->toBe($b['bursts'][0]['options']['origin']);
    });

    it('carries the official fireworks values into the expanded bursts', function (): void {
        $payload = Confetti::fireworks(1000)->expand()->toArray();

        expect(burstOption($payload, 'spread'))->toBe(360.0);
        expect(burstOption($payload, 'ticks'))->toBe(60);
        expect(burstOption($payload, 'zIndex'))->toBe(0);
    });

    it('refuses to expand into an unreasonable number of bursts', function (): void {
        // The point of the ceiling: without it, expand() on a default-duration
        // snowfall quietly produces a payload in the hundreds of kilobytes.
        expect(fn () => Confetti::snow(60000)->expand()->toPayload())
            ->toThrow(InvalidOption::class, 'over the configured limit');
    });
});

describe('JSON encoding', function (): void {
    it('escapes a script-closing sequence in user-supplied text', function (): void {
        // The boot payload is written into a <script type="application/json">
        // block. A literal </script> inside it would close the element early
        // and turn everything after it into markup — and text reaching
        // shapeFromText is the realistic route for user input to get there.
        $encoded = Json::encode(['text' => '</script><img src=x onerror=alert(1)>']);

        // The property that matters: no angle bracket survives as a literal, so
        // the HTML parser cannot find a tag to act on. They leave as unicode
        // escapes, which JSON.parse turns back into the original characters.
        expect(str_contains($encoded, '<'))->toBeFalse();
        expect(str_contains($encoded, '>'))->toBeFalse();
        expect($encoded)->toContain(trim((string) json_encode('<', JSON_HEX_TAG), '"'));
    });

    it('round-trips the escaped text unchanged', function (): void {
        $text = '</script>🦄&"\'';

        expect(json_decode(Json::encode(['t' => $text]), true)['t'])->toBe($text);
    });

    it('escapes it through the full builder path too', function (): void {
        $json = Confetti::shapeFromText('</script>')->toJson();
        $encoded = Json::encode(json_decode($json, true));

        expect($encoded)->not->toContain('</script>');
    });
});

describe('the envelope', function (): void {
    it('carries a version and a unique id', function (): void {
        $payload = Confetti::count(10)->toArray();

        expect($payload['v'])->toBe(ConfettiPayload::VERSION);
        expect($payload['id'])->toBeString()->not->toBe(Confetti::count(10)->toArray()['id']);
    });

    it('round-trips through an array', function (): void {
        $original = Confetti::realistic()->toPayload();
        $restored = ConfettiPayload::fromArray($original->toArray());

        expect($restored->toArray())->toBe($original->toArray());
    });

    it('merges two payloads into one effect', function (): void {
        $merged = Confetti::count(10)->toPayload()->merge(Confetti::count(20)->toPayload());

        expect($merged->burstCount())->toBe(2);
    });

    it('builds a stop instruction', function (): void {
        expect(ConfettiPayload::stop()->action)->toBe(ConfettiPayload::ACTION_STOP);
        expect(ConfettiPayload::stop()->isEmpty())->toBeFalse();
    });
});
