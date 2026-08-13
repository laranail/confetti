# Summary

<!-- What changes, and why. The why matters more. -->

## Checklist

- [ ] `composer lint` passes (Pint, PHPStan, Rector, greps)
- [ ] `composer test` passes
- [ ] `npm test` passes, if the browser runtime changed
- [ ] `npm run build` was run and `resources/dist` committed, if `resources/js` changed
- [ ] `CHANGELOG.md` has an entry under `## [Unreleased]`, if this is user-facing

## Notes for the reviewer

<!--
If you changed anything about how the package detects Livewire, Inertia or
Filament, say so here — those are duck-typed on purpose so the package works
without them, and it is easy to "tidy" that into a hard dependency.
-->
