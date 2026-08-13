# Release

How a version of this package is cut.

## Before tagging

```bash
composer update            # sibling laranail packages resolve from a moving tag
composer lint              # Pint, PHPStan, Rector, greps
composer test              # the full Pest suite
npm install && npm test         # the browser runtime
npm run build              # rebuild the committed bundle
git diff --exit-code resources/dist
```

The last two matter more than they look. `resources/dist` is tracked, because
consumers install through Composer and never run npm, so a bundle that has
drifted from its source ships stale behaviour to every application. CI fails on
that drift, and so should you before tagging.

Run the org-wide shipping checklist as well.

## Versioning

Semantic versioning. While pre-1.0 the laranail family keeps a single `v0.1.0`
tag per repository and moves it, so consumers on `^0.1` pick up changes on their
next `composer update`.

Two things count as breaking even though they look cosmetic:

- **The wire payload version.** `ConfettiPayload::VERSION` is checked by the
  runtime, which refuses a payload from a newer package than the bundle it is
  running. Bumping it requires a matching bundle rebuild.
- **A preset's numbers.** People screenshot these. Changing what `realistic()`
  looks like is a visible change to every application using it, and belongs in
  the changelog even though no signature moved.

## Changelog

Every release needs a real description, sourced from that version's
`CHANGELOG.md` section, not auto-generated notes and not "see the changelog".
`release.yml` extracts the `## [X.Y.Z]` block and uses it as the release body,
so the changelog entry is the release notes.

## Tagging

```bash
git tag -a v0.1.0 -m "Initial release"
git push origin v0.1.0
```

The release workflow then installs without dev dependencies, generates a
CycloneDX SBOM, extracts the changelog section, and publishes the GitHub release
with the SBOM attached.

## Packagist

laranail packages resolve their inter-package dependencies through git VCS
repositories rather than Packagist, so a release needs no Packagist step and
none should be taken as part of routine work.

---

[← Docs index](../README.md#documentation)
