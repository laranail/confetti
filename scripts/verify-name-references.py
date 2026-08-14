#!/usr/bin/env python3
"""Find references to a package's old, unprefixed registered names.

Renaming a registered name is not a single edit. The name reappears in forms a
naive `slug::` search does not reach, and every one of these was missed by hand
during the laranail rename:

  x-slug::component      a Blade tag, where the prefix is glued to `x-`
  'slug.alias:60,1'      a middleware alias carrying parameters, so the name is
                         not followed by a closing quote
  addLines([...], 'fr', 'slug')
                         a namespace passed as a bare argument, with no `::` at
                         all; the same shape as loadViewsFrom and loadJsonFrom
  "/aliasMiddleware\\('([a-z.]+)'/"
                         a test that extracts names with a character class that
                         cannot match the hyphen in the new one, so it silently
                         matches nothing and fails on its own emptiness guard

It also refuses to flag what must not move. `impersonator.mode` is both a
middleware alias and a Gate ability; they are different registries that happen
to share a string, and renaming the ability because the middleware moved would
be a new bug in the name of fixing an old one.

Usage:
    verify-name-references.py <package-dir> [<package-dir> ...]

Exits non-zero if any stale reference to a bare name survives.
"""

from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

SEARCHED = ("src", "tests", "docs", "resources", "config", "routes", "database")
EXTRA_FILES = ("README.md", "UPGRADING.md", "CHANGELOG.md")

# Registries whose keys are global and therefore must carry the vendor.
REGISTRARS = (
    "hasViews",
    "hasTranslations",
    "hasBladeComponentNamespace",
    "aliasMiddleware",
    "registerRouteMiddleware",
)

# Calls that take a namespace as a bare argument rather than an inline `ns::`.
# The name is the LAST string argument in each.
BARE_ARGUMENT = re.compile(
    r"\b(addLines|addNamespace|loadViewsFrom|loadTranslationsFrom|loadJsonTranslationsFrom)"
    r"\s*\((?P<args>[^;]*?)\)",
    re.S,
)

# A dotted string only counts as a middleware alias when it is in a middleware
# position. Without this the check is useless: `slug.something` is also how
# config keys, route names, view names and Gate abilities are spelled, so
# matching the shape alone reported 211 findings in impersonator, none real.
MIDDLEWARE_CONTEXT = re.compile(
    r"\bmiddleware\s*\(|\bmiddleware'\s*=>|aliasMiddleware\s*\(|registerRouteMiddleware\s*\(|"
    r"\bwithMiddleware\b|'middleware'\s*=>"
)

# `slug.something` is only an alias when `something` is not a file extension.
# Without this, a package called confetti reports its own bundle, confetti.js,
# as a middleware alias it forgot to rename.
EXTENSIONS = {
    "js", "mjs", "cjs", "ts", "tsx", "jsx", "vue", "css", "scss", "php", "json",
    "md", "html", "xml", "yml", "yaml", "lock", "txt", "png", "jpg", "svg",
    "gif", "webp", "ico", "sh", "py", "blade", "env", "log", "sql", "csv",
}


def sh(*args: str) -> str:
    return subprocess.run(args, capture_output=True, text=True).stdout


def registered_names(package: Path) -> set[str]:
    """Every name the package registers, read from the providers."""
    names: set[str] = set()
    for source in (package / "src").rglob("*.php"):
        code = strip_comments(source)
        for registrar in REGISTRARS:
            if registrar == "hasBladeComponentNamespace":
                pattern = rf"{registrar}\(\s*'[^']+'\s*,\s*'([^']+)'"
            else:
                pattern = rf"{registrar}\(\s*'([^']+)'"
            names.update(re.findall(pattern, code, re.S))
    return names


def strip_comments(source: Path) -> str:
    php = (
        "$t = token_get_all(file_get_contents($argv[1]));"
        "foreach ($t as $x) {"
        "  if (is_array($x)) {"
        "    if ($x[0] === T_COMMENT || $x[0] === T_DOC_COMMENT) { continue; }"
        "    echo $x[1];"
        "  } else { echo $x; }"
        "}"
    )
    out = subprocess.run(
        ["php", "-r", php, str(source)], capture_output=True, text=True
    )
    return out.stdout if out.returncode == 0 else source.read_text(errors="replace")


def files_of(package: Path) -> list[Path]:
    found: list[Path] = []
    for directory in SEARCHED:
        base = package / directory
        if base.is_dir():
            found += [p for p in base.rglob("*") if p.is_file() and p.suffix in
                      {".php", ".md", ".blade", ".json", ".yml", ".yaml", ".js", ".ts"}
                      or p.name.endswith(".blade.php")]
    found += [package / name for name in EXTRA_FILES if (package / name).exists()]
    return found


def slug_of(package: Path) -> str | None:
    composer = package / "composer.json"
    if not composer.exists():
        return None
    name = json.loads(composer.read_text()).get("name", "")
    return name.split("/", 1)[1] if "/" in name else None


def audit(package: Path) -> list[str]:
    slug = slug_of(package)
    if slug is None:
        return []

    stale = [n for n in registered_names(package) if not n.startswith("laranail-")]
    findings: list[str] = []

    # The bare forms a rename must also catch, whether or not the registration
    # itself was already moved.
    bare = {slug}
    bare.update(n.split(".", 1)[0] for n in stale)
    bare = {b for b in bare if b and not b.startswith("laranail-")}

    for name in sorted(stale):
        findings.append(f"  REGISTERED  '{name}' is still registered unprefixed")

    for path in files_of(package):
        try:
            text = path.read_text(errors="replace")
        except OSError:
            continue

        rel = path.relative_to(package)

        for base in sorted(bare):
            for number, line in enumerate(text.splitlines(), 1):
                # `ns::` in any position, including glued to an x- tag.
                #
                # The lookbehind has to admit `x-slug::` while still rejecting
                # `laranail-slug::`, so it accepts a preceding `x-` explicitly
                # rather than banning every hyphen. Banning them all is the
                # obvious spelling and it silently skips every Blade tag.
                #
                # `/` is excluded too, because `laranail/slug::` is the full
                # namespace package-tools always registers. That one is correct
                # and must not be reported as a leftover.
                if re.search(rf"(?:(?<=x-)|(?<![\w/-])){re.escape(base)}::", line):
                    findings.append(f"  REF         {rel}:{number}  {base}::")
                    continue

                # A dotted alias, with or without :parameters, in a middleware
                # position. Gate abilities are excluded by context.
                alias = re.search(rf"'{re.escape(base)}\.([a-z-]+)(?::[^']*)?'", line)
                if (
                    alias
                    and alias.group(1) not in EXTENSIONS
                    and MIDDLEWARE_CONTEXT.search(line)
                ):
                    findings.append(f"  ALIAS       {rel}:{number}  {alias.group(0)}")
                    continue

            # A namespace handed over as a bare argument.
            for call in BARE_ARGUMENT.finditer(text):
                args = call.group("args")
                trailing = re.findall(r"'([^']+)'\s*$", args.strip())
                if trailing and trailing[-1] == base:
                    number = text[: call.start()].count("\n") + 1
                    findings.append(
                        f"  BARE ARG    {rel}:{number}  {call.group(1)}(..., '{base}')"
                    )

        # A regex that extracts names but cannot match a hyphen will silently
        # match nothing once the names are prefixed.
        for number, line in enumerate(text.splitlines(), 1):
            if re.search(r"|".join(REGISTRARS), line) and "preg_match" in line:
                classes = re.findall(r"\[([^\]]*)\]", line)
                # A class matches a literal hyphen only when the hyphen is
                # first, last, or escaped. Asking whether the class "contains a
                # hyphen" says yes to `[a-z.]`, where the hyphen is a range.
                if classes and not any(
                    c.startswith("-") or c.endswith("-") or "\\-" in c for c in classes
                ):
                    findings.append(
                        f"  REGEX       {rel}:{number}  extracts names but cannot match a hyphen"
                    )

    return sorted(set(findings))


def main(argv: list[str]) -> int:
    packages = [Path(a) for a in argv[1:]] or [Path.cwd()]
    total = 0

    for package in packages:
        findings = audit(package)
        if not findings:
            continue
        print(f"{package.name}:")
        for line in findings:
            print(line)
        total += len(findings)

    if total:
        print()
        print(f"  {total} stale reference(s) to an unprefixed name.")
        return 1

    print("  No stale references to unprefixed names.")
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
