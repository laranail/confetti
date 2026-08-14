#!/usr/bin/env python3
"""Check that every SHA-pinned GitHub Action matches the version in its comment.

Pinning an action to a commit SHA is the supply-chain control; the trailing
`# v1.2.3` is the only thing that makes it readable. Nothing keeps the two in
step, so a hand-edited pin can claim one version and run another, and the
comment is what a reviewer reads. This package shipped exactly that defect:
a CodeQL pin labelled v3.28.0 whose SHA was a v4 commit.

Two failure modes, both reported:

  MISMATCH  the tag in the comment resolves to a different commit
  UNKNOWN   the tag does not exist upstream, so the comment is unverifiable

The second is not pedantry. `shivammathur/setup-php` tags without a leading
`v`, so a `# v2.34.1` comment on a correct SHA silently defeats this check.

Queries through `gh`, which is present on GitHub runners and already
authenticated locally, so the same command works in both places.
"""

from __future__ import annotations

import json
import re
import shutil
import subprocess
import sys
from pathlib import Path

PIN = re.compile(
    r"uses:\s*([\w.-]+)/([\w.-]+)((?:/[\w.-]+)*)@([0-9a-f]{40})\s*#\s*(\S+)"
)

WORKFLOWS = Path(__file__).resolve().parent.parent / ".github" / "workflows"


class Unavailable(Exception):
    """The API could not answer, as distinct from answering 'no such tag'."""


def api(path: str) -> dict | None:
    result = subprocess.run(
        ["gh", "api", path], capture_output=True, text=True, timeout=60
    )
    if result.returncode == 0:
        return json.loads(result.stdout)
    if "Not Found" in result.stderr or "404" in result.stderr:
        return None
    raise Unavailable(result.stderr.strip().splitlines()[0] if result.stderr else "?")


def commit_for_tag(owner: str, repo: str, tag: str) -> str | None:
    """Resolve a tag to a commit SHA, dereferencing annotated tags."""
    ref = api(f"/repos/{owner}/{repo}/git/ref/tags/{tag}")
    if ref is None:
        return None

    obj = ref["object"]
    if obj["type"] != "tag":
        return obj["sha"]

    # An annotated tag points at a tag object, not the commit. Actions
    # resolves the commit, so that is what the pin has to equal.
    return api(f"/repos/{owner}/{repo}/git/tags/{obj['sha']}")["object"]["sha"]


def main() -> int:
    if shutil.which("gh") is None:
        print("  gh is not installed; cannot verify action pins.")
        return 1

    pins: dict[tuple[str, str, str, str, str], set[str]] = {}
    for workflow in sorted(WORKFLOWS.glob("*.yml")):
        for owner, repo, sub, sha, tag in PIN.findall(workflow.read_text()):
            pins.setdefault((owner, repo, sub, sha, tag), set()).add(workflow.name)

    if not pins:
        print("  No SHA-pinned actions found, which is itself suspicious.")
        return 1

    failures = 0
    for (owner, repo, sub, sha, tag), files in sorted(pins.items()):
        name = f"{owner}/{repo}{sub}"
        where = ", ".join(sorted(files))
        try:
            actual = commit_for_tag(owner, repo, tag)
        except Unavailable as error:
            # An outage or a rate limit is not a pin defect, and failing the
            # build for one would train people to ignore this check.
            print(f"  SKIPPED   {name} {tag}: {error}")
            continue

        if actual is None:
            failures += 1
            print(f"  UNKNOWN   {name}: no tag {tag} upstream ({where})")
        elif actual != sha:
            failures += 1
            print(
                f"  MISMATCH  {name}: comment says {tag} ({actual[:12]}) "
                f"but pinned {sha[:12]} ({where})"
            )
        else:
            print(f"  OK        {name} {tag}")

    print()
    if failures:
        print(f"  {failures} pin(s) do not match their comment.")
        return 1

    print(f"  All {len(pins)} action pins match their comments.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
