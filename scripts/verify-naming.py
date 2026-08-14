#!/usr/bin/env python3
"""Check that every name this package registers carries the vendor and slug.

Laravel keeps view namespaces, translation namespaces, Blade component prefixes
and route middleware aliases in flat maps keyed by the name. Two packages
claiming the same key do not conflict loudly: the second silently replaces the
first, and the damage surfaces far away as a missing view or the wrong
middleware running. A slug like `confetti`, `console`, `demo` or `license` is a
plausible collision with a sibling package, a third-party one, or the consuming
application's own code.

The convention, in `laranail/CLAUDE.md`:

    Artisan command      laranail::<slug>.<command>
    Everything else      laranail-<slug>

Commands take `::` because Symfony resolves an exact name before splitting on
`:`. Nothing else can: Laravel splits middleware aliases on `:` to take
parameters, and in a Blade tag `::` already separates prefix from component.

Two defects are reported, and the second is the worse one:

    BARE      registered without the laranail- prefix
    MISMATCH  the name does not even match the package slug, so it is
              unguessable as well as collision-prone

Severity is not uniform across the surfaces, and the difference matters when
deciding what to fix. A view namespace, a component prefix and a middleware
alias are each the *only* registration for that name, so a bare one leaves the
package genuinely unnamespaced. A translation alias is *additional*:
package-tools always registers `vendor/package::` and `hasTranslations($alias)`
adds a second, shorter namespace beside it. A bare alias there can still be
taken by someone else, but dropping it costs only brevity.

Run from a package root, or pass one or more package directories.
"""

from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

# (regex, what kind of name it registers, whether a suffix may follow the slug)
PATTERNS: list[tuple[re.Pattern[str], str, bool]] = [
    (re.compile(r"hasViews\(\s*'([^']+)'"), "view namespace", False),
    (re.compile(r"hasTranslations\(\s*'([^']+)'"), "translation alias", False),
    (
        re.compile(r"hasBladeComponentNamespace\(\s*'[^']+'\s*,\s*'([^']+)'", re.S),
        "component prefix",
        False,
    ),
    (re.compile(r"aliasMiddleware\(\s*'([^']+)'"), "middleware alias", True),
    (re.compile(r"registerRouteMiddleware\(\s*'([^']+)'"), "middleware alias", True),
    (re.compile(r"[Aa]lpine\.data\(\s*'([^']+)'"), "alpine component", False),
]

# Registers a whole map at once, so the names are the array's keys rather than
# an argument. It needs its own pass: matching the call gives the array body,
# and reading that as a name reports the class list as a namespace. db-console
# registered a bare alias through this and the audit could not see it at all.
ALIAS_MAP = re.compile(r"registerMiddlewareAliases\(\s*\[(?P<body>.*?)\]", re.S)
ALIAS_KEY = re.compile(r"'([^']+)'\s*=>")

# Laravel's own registrars, used directly rather than through package-tools.
# Checking only the fluent wrappers passed enumerator, console and vigilance as
# compliant while they registered bare namespaces through these instead, so the
# audit has to read what Laravel is told, not what the builder was told.
# The namespace is the last string argument.
DIRECT = [
    (re.compile(r"loadViewsFrom\(\s*(?P<args>[^;]*?)\)", re.S), "view namespace", False),
    (
        re.compile(r"loadTranslationsFrom\(\s*(?P<args>[^;]*?)\)", re.S),
        "translation namespace",
        False,
    ),
    (
        re.compile(r"Blade::componentNamespace\(\s*(?P<args>[^;]*?)\)", re.S),
        "component prefix",
        False,
    ),
    (
        re.compile(r"anonymousComponentNamespace\(\s*(?P<args>[^;]*?)\)", re.S),
        "component prefix",
        False,
    ),
]

LAST_STRING = re.compile(r"'([^']*)'\s*,?\s*$")


def without_comments(source: Path) -> str:
    """The file's code with comments removed, via PHP's own tokenizer.

    Necessary, not fastidious: package-tools documents `hasTranslations('widget')`
    in an @example docblock, and a plain regex read that as a registration and
    reported the package as violating a convention it does not violate.
    """
    php = (
        "$t = token_get_all(file_get_contents($argv[1]));"
        "foreach ($t as $x) {"
        "  if (is_array($x)) {"
        "    if ($x[0] === T_COMMENT || $x[0] === T_DOC_COMMENT) { continue; }"
        "    echo $x[1];"
        "  } else { echo $x; }"
        "}"
    )
    result = subprocess.run(
        ["php", "-r", php, str(source)], capture_output=True, text=True
    )
    return result.stdout if result.returncode == 0 else source.read_text(errors="replace")


def slug_of(package: Path) -> str | None:
    """The package slug, or None when this convention does not apply.

    Only laranail's own packages are held to it. Several directories here are
    forks of third-party packages that keep their upstream vendor, such as
    anousss007/vigilance and devifyo/watchtower. Renaming their namespaces
    would diverge from the upstream they track, to satisfy a convention that
    was never theirs.
    """
    composer = package / "composer.json"
    if not composer.exists():
        return None

    name = json.loads(composer.read_text()).get("name", "")
    if not name.startswith("laranail/"):
        return None

    return name.split("/", 1)[1]


def camel(slug: str) -> str:
    head, *rest = slug.split("-")
    return head + "".join(part.title() for part in rest)


def audit(package: Path) -> list[str]:
    slug = slug_of(package)
    if slug is None:
        return [f"  SKIPPED   {package.name}: not a laranail package"]

    findings: list[str] = []
    expected = f"laranail-{slug}"

    for source in sorted((package / "src").rglob("*.php")):
        text = without_comments(source)
        where = f"{source.relative_to(package)}"

        for pattern, kind, _ in DIRECT:
            for call in pattern.finditer(text):
                args = call.group("args").strip()

                # A non-greedy match stops at the first `)`, which belongs to a
                # nested call rather than this one. Unbalanced parens mean the
                # arguments were cut short, and reading a "namespace" out of the
                # fragment picks up the inner call's argument instead:
                # Blade::componentNamespace(config('modules.namespace')..., $x)
                # reported the config key as a bare component prefix.
                if args.count("(") != args.count(")"):
                    findings.append(
                        f"  UNVERIFIED {kind} is computed, not a literal, in {where}"
                    )
                    continue

                match = LAST_STRING.search(args)

                if match is None:
                    # A computed namespace cannot be checked here, and silently
                    # passing it is how these were missed in the first place.
                    findings.append(
                        f"  UNVERIFIED {kind} is computed, not a literal, in {where}"
                    )
                    continue

                name = match.group(1)

                # loadViewsFrom($path) with no namespace registers no namespace.
                if name == "" or "/" in name or name == expected:
                    continue

                stem = name.removeprefix("laranail-")
                label = "BARE" if stem == slug else "MISMATCH"
                findings.append(
                    f"  {label:<9} {kind} '{name}' (expected '{expected}') in {where}"
                )

        for call in ALIAS_MAP.finditer(text):
            for name in ALIAS_KEY.findall(call.group("body")):
                if name == expected or name.startswith(f"{expected}."):
                    continue
                stem = name.split(".", 1)[0].removeprefix("laranail-")
                label = "BARE" if stem == slug else "MISMATCH"
                findings.append(
                    f"  {label:<9} middleware alias '{name}' "
                    f"(expected '{expected}[.suffix]') in {where}"
                )

        for pattern, kind, allow_suffix in PATTERNS:
            for match in pattern.finditer(text):
                name = match.group(1)
                where = f"{source.relative_to(package)}"

                # Alpine names are camelCase, because x-data is evaluated as a
                # JavaScript expression and a hyphen reads as subtraction.
                if kind == "alpine component":
                    if name != camel(f"laranail-{slug}"):
                        findings.append(
                            f"  BARE      {kind} '{name}' "
                            f"(expected '{camel(f'laranail-{slug}')}') in {where}"
                        )
                    continue

                ok = name == expected or (
                    allow_suffix and name.startswith(f"{expected}.")
                )
                if ok:
                    continue

                # Distinguish "unprefixed" from "not even this package's name",
                # because the second cannot be guessed by a reader either.
                stem = name.split(".", 1)[0].removeprefix("laranail-")
                label = "BARE" if stem == slug else "MISMATCH"
                suffix = "[.suffix]" if allow_suffix else ""
                findings.append(
                    f"  {label:<9} {kind} '{name}' "
                    f"(expected '{expected}{suffix}') in {where}"
                )

    return findings


def main(argv: list[str]) -> int:
    packages = [Path(a) for a in argv[1:]] or [Path.cwd()]

    total = 0
    for package in packages:
        findings = audit(package)
        if not findings:
            continue

        if len(packages) > 1:
            print(f"{package.name}:")
        for line in findings:
            print(line)
        total += sum(1 for f in findings if "SKIPPED" not in f)

    if total:
        print()
        print(f"  {total} name(s) can collide with another package.")
        return 1

    print("  Every registered name carries the vendor and slug.")
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
