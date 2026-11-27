# ADR-0001 — Stack and modular architecture

- Status: **Accepted** (Sprint 0)
- Date: 2026-11-27

## Context

The project needs a maintainable, self-hostable PHP application that a distributed open-source team can contribute to without heavy infrastructure. Sprint 0 must not implement school features, but it must make later feature work predictable. The available host toolchain has PHP 8.3, Composer 2, Docker Compose, Node 24 and GitHub CLI. Packagist currently offers Laravel 13.44.1 (requires PHP ^8.3).

## Decision

- Use **Laravel 13** on **PHP 8.3** as the single application framework. Commit `composer.lock`; CI and Docker install from it.
- Keep **one deployable monolith**. Within it, organise domain code in `app/Domains/<Context>/` (for example `Actions`, `Models`, `Services`, `Requests`, `Resources`) and shared technical concerns in `app/Support/`. HTTP controllers and routes stay thin.
- Enforce boundaries with conventions and, later, Pest architecture tests. Do not add a third-party module package in Sprint 0.
- **No microservices, no service mesh, no separate queues or caches** until a concrete scaling or team-ownership need is documented in a new ADR.
- Minimal Vite/Laravel-plugin/Tailwind asset build infrastructure is retained in Sprint 0 as foundation tooling, not as a frontend architecture decision. UI frameworks, component libraries, application views and UI dependencies remain frozen until ADR-0003 is accepted.

## Alternatives considered

| Alternative | Reason not chosen |
| --- | --- |
| Microservices per school domain | Operational cost far exceeds the needs of an open-source, self-hosted system; distributed transactions add complexity. |
| `nwidart/laravel-modules` package | Adds a package dependency and module lifecycle complexity before any module exists. Revisit if module reuse becomes real. |
| Laravel 12 / PHP 8.2 | Newer supported Laravel release is available and the host already has PHP 8.3. |
| Plain PHP MVC | Reinvents routing, migrations, auth and testing that Laravel already provides. |

## Consequences

Positive: one codebase, one test suite, simple local setup, familiar conventions for contributors. Negative: domain boundaries rely on discipline until architecture tests land; a very large team would eventually need stronger enforcement.

## Review trigger

Revisit if a domain needs independent deployment/scaling, or if module reuse across installations becomes a requirement.
