{{--
    laranail/confetti: the boot payload and the browser runtime.

    Two elements, and no inline JavaScript in either.

    The first is a JSON data block. Browsers do not execute a script element
    whose type is not a JavaScript MIME type, so this needs no CSP allowance and
    carries no nonce. It is encoded through Support\Json, which escapes `<`, `>`,
    `&`, `'` and `"`. That escaping is what stops a `</script>` sequence in
    user-supplied text (an emoji label passed to shapeFromText, say) from closing
    the element early and turning the rest of the payload into markup.

    The second is the runtime, always an external source. It reads the block
    above on load, so nothing about the effect needs to be inlined.
--}}
@if ($enabled)
    <script type="application/json" data-confetti-boot>{!! $bootJson !!}</script>
    {!! $scriptTag !!}
@endif
