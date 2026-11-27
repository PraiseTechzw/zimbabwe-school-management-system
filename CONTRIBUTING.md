# Contributing

Thank you for helping build Zimbabwe-first, open-source education software.

## Before you start

- Read [AGENTS.md](AGENTS.md), [engineering conventions](docs/development/engineering-conventions.md) and [ADR-0001](docs/adr/0001-stack-and-modular-architecture.md).
- Sprint 0 is foundation-only: no school features are being implemented.
- Pick an issue or open one using the appropriate template. Comment to claim it.

## Setup

Follow [environment setup](docs/development/setup.md). Docker Compose is the reproducible environment; `make verify` runs all quality gates.

## Workflow

1. Create a branch from `main` (`feature/`, `fix/`, `chore/`, `research/`, `docs/`).
2. Make a focused change with tests and documentation updates.
3. Run `make verify` (or the equivalent local commands) and record the output.
4. Open a PR with the template; link the issue and evidence.
5. Respond to review; a human merges via squash. Never push to `main`.

## What reviewers check

Scope, behaviour, tests, security/privacy, docs, ADR impact, and passing CI checks (`quality`, `tests`, `docker`).

## Reporting security issues

Do not open a public issue. Use [SECURITY.md](SECURITY.md).
