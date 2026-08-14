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

Run from a package root, or pass one or more package directories.
"""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

# (regex, what kind of name it registers, whether a suffix may follow the slug)
PATTERNS: list[tuple[re.Pattern[str], str, bool]] = [
    (re.compile(r"hasViews\(\s*'([^']+)'"), "view namespace", False),
    (re.compile(r"hasTranslations\(\s*'([^']+)'"), "translation namespace", False),
    (
        re.compile(r"hasBladeComponentNamespace\(\s*'[^']+'\s*,\s*'([^']+)'", re.S),
        "component prefix",
        False,
    ),
    (re.compile(r"aliasMiddleware\(\s*'([^']+)'"), "middleware alias", True),
    (re.compile(r"registerRouteMiddleware\(\s*'([^']+)'"), "middleware alias", True),
    (re.compile(r"[Aa]lpine\.data\(\s*'([^']+)'"), "alpine component", False),
]


def slug_of(package: Path) -> str | None:
    composer = package / "composer.json"
    if not composer.exists():
        return None

    name = json.loads(composer.read_text()).get("name", "")
    return name.split("/", 1)[1] if "/" in name else None


def camel(slug: str) -> str:
    head, *rest = slug.split("-")
    return head + "".join(part.title() for part in rest)


def audit(package: Path) -> list[str]:
    slug = slug_of(package)
    if slug is None:
        return [f"  SKIPPED   {package.name}: no composer name to check against"]

    findings: list[str] = []
    expected = f"laranail-{slug}"

    for source in sorted((package / "src").rglob("*.php")):
        text = source.read_text(errors="replace")

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
