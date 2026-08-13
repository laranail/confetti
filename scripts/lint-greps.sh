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
echo "  laranail/confetti — lint greps"
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
# as a broken image on Packagist — which is exactly what happened to the package
# this one replaces.
if grep -nE '\]\(\.?/?art/' README.md 2>/dev/null; then
    fail 'README.md links a repository-relative image from /art, which is export-ignored.'
else
    pass 'No repository-relative images in the README'
fi

# 5. The built bundle carries its third-party licence notice.
if [ -f resources/dist/confetti.iife.js ]; then
    if grep -q 'Kiril Vatev' resources/dist/confetti.iife.js; then
        pass 'Bundle carries the canvas-confetti ISC notice'
    else
        fail 'The built bundle has lost the canvas-confetti ISC notice.'
    fi
else
    fail 'The browser bundle is missing — run npm run build.'
fi

echo

if [ "$failures" -gt 0 ]; then
    printf '\033[31m  %d check(s) failed.\033[0m\n\n' "$failures"
    exit 1
fi

printf '\033[32m  All checks passed.\033[0m\n\n'
