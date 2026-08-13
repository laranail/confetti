#!/usr/bin/env bash
#
# Gating greps for claims the type system cannot check.
#
# Each rule here corresponds to something this package promises in its
# documentation. A promise that is only in prose drifts; a promise with a grep
# behind it does not.
#
set -euo pipefail

cd "$(dirname "$0")/.."

failures=0

fail() {
    printf '\033[31m  ✗\033[0m %s\n' "$1"
    failures=$((failures + 1))
}

pass() {
    printf '\033[32m  ✓\033[0m %s\n' "$1"
}

echo
echo "  laranail/confetti lint greps"
echo

# 1. No executable inline script in a Blade view.
#
# The runtime is always an external file, and the boot payload is a JSON data
# block. That is what lets the package work under a strict Content-Security-Policy
# without an unsafe-inline allowance.
if grep -rnE '<script(?![^>]*type="application/json")[^>]*>[^<]' --include='*.blade.php' -P resources/views 2>/dev/null; then
    fail 'A Blade view contains an inline <script> with a body.'
else
    pass 'No inline scripts in views'
fi

# 2. No unescaped output in a Blade view, except the two places that are
#    deliberately raw and are encoded by Support\Json instead.
if grep -rn '{!!' --include='*.blade.php' resources/views \
    | grep -vE '\$bootJson|\$scriptTag' 2>/dev/null; then
    fail 'A Blade view echoes unescaped output outside the encoded boot payload.'
else
    pass 'No unaudited unescaped output'
fi

# 3. No inline event handlers.
if grep -rnE '\son(click|load|error|submit|change|mouse[a-z]+)=' --include='*.blade.php' resources/views 2>/dev/null; then
    fail 'A Blade view uses an inline event handler attribute.'
else
    pass 'No inline event handlers'
fi

# 4. The README links no repository-relative image.
#
# /art is export-ignored from the Composer tarball, so a relative link renders
# as a broken image on Packagist, which is exactly what happened to the package
# this one replaces.
if grep -nE '\]\(\.?/?art/' README.md 2>/dev/null; then
    fail 'README.md links a repository-relative image from /art, which is export-ignored.'
else
    pass 'No repository-relative images in the README'
fi

# 5. No em-dashes, en-dashes or ellipsis glyphs in prose or code comments.
#
# The org shipping checklist bans them outright. This was cleaned up by hand
# once and regressed within a day, because the check lived in a shell history
# rather than here. The glyphs are built with printf so this file does not trip
# its own rule, and every grep is guarded with `|| true`, since `set -e` treats
# "no matches" as a failure.
EM=$(printf '\342\200\224')
EN=$(printf '\342\200\223')
EL=$(printf '\342\200\246')

GLYPH_HITS=$(git ls-files \
    | grep -vE '^(art/|resources/dist/|scripts/lint-greps\.sh)' \
    | xargs grep -nE "$EM|$EN|$EL" 2>/dev/null || true)

if [ -n "$GLYPH_HITS" ]; then
    printf '%s\n' "$GLYPH_HITS" | head -10 | sed 's/^/      /'
    fail 'Em-dash, en-dash or ellipsis glyph found. Use a comma, colon, full stop or three dots.'
else
    pass 'No banned punctuation glyphs'
fi

# 6. The built bundle carries its third-party licence notice.
if [ -f resources/dist/confetti.iife.js ]; then
    if grep -q 'Kiril Vatev' resources/dist/confetti.iife.js; then
        pass 'Bundle carries the canvas-confetti ISC notice'
    else
        fail 'The built bundle has lost the canvas-confetti ISC notice.'
    fi
else
    fail 'The browser bundle is missing. Run npm run build.'
fi

echo

if [ "$failures" -gt 0 ]; then
    printf '\033[31m  %d check(s) failed.\033[0m\n\n' "$failures"
    exit 1
fi

printf '\033[32m  All checks passed.\033[0m\n\n'
