<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Confetti\Support;

use JsonException;

/**
 * JSON encoding for values that get embedded in HTML.
 *
 * The boot payload is written into a `<script type="application/json">` element,
 * which the browser treats as inert data — but only until the byte sequence
 * `</script>` appears inside it. At that point the HTML parser closes the
 * element early and everything after it becomes markup. Text supplied by a user
 * and passed to `shapeFromText()` is the realistic route to that happening.
 *
 * `JSON_HEX_TAG` escapes `<` and `>` to `<` / `>`, which is what
 * actually prevents it; the other three flags close the same class of hole for
 * attribute contexts and for `&` entity tricks. `JSON.parse` decodes all four
 * escapes back to the original characters, so nothing is lost — a literal
 * `</script>` in an emoji label still arrives intact at the runtime.
 *
 * Keep the flags. They are the reason this class exists rather than a call to
 * `json_encode()` at each site.
 */
final class Json
{
    public const HTML_SAFE_FLAGS = JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE;

    /**
     * Encode a value for embedding in an HTML document.
     *
     * @throws JsonException
     */
    public static function encode(mixed $value, int $extraFlags = 0): string
    {
        return json_encode($value, self::HTML_SAFE_FLAGS | JSON_THROW_ON_ERROR | $extraFlags);
    }

    /**
     * Encode for a context that is not HTML — a test assertion, a log line, an
     * API response. Uses the same flags minus the HTML escaping, so the output
     * stays readable.
     *
     * @throws JsonException
     */
    public static function encodePlain(mixed $value, int $extraFlags = 0): string
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | $extraFlags
        );
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws JsonException
     */
    public static function decode(string $json): array
    {
        return (array) json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
