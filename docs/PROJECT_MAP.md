# Project Map

This document is maintained as the repository evolves.

## Documentation

- `AGENTS.md` — agent operating rules
- `PRD.md` — product requirements
- `architecture.md` — technical architecture
- `CONTRIBUTING.md` — contribution workflow
- `DECISIONS.md` — architecture decisions
- `CHANGELOG.md` — notable project changes

## Application

Use the actual repository structure as the source of truth.

Before adding files, search for an existing location serving the same purpose.

## Database

Database migrations and schema definitions must remain in the database tooling structure documented by `architecture.md`.

## Tests

Use the existing test structure.

Do not create a second testing framework when an existing framework already covers the requirement.

## Maintenance

Update this file when major directories or architectural boundaries change.
