# ADR-0003 — Frontend strategy

- Status: **Proposed — final UI framework decision required before any application UI implementation**
- Date: 2026-11-27
- Updated: 2026-11-27 (Sprint 0.1 — scope clarification only)

## Context

Sprint 0 must evaluate the frontend before implementation. Constraints: low-bandwidth and shared-device usage in some Zimbabwean schools, open-source contributors with mixed frontend experience, self-hosted deployment, and a small foundation team. No application UI code is part of Sprint 0.

The repository does contain a minimal asset build scaffold (Vite, the Laravel Vite plugin and Tailwind CSS). That scaffold is **build infrastructure**, not an approved frontend architecture. It exists to keep CSS/JS compilation reproducible and verifiable in CI without inventing an asset pipeline later.

## Sprint 0 scope clarification

The following are permitted as foundation infrastructure:

- `package.json` with `build` and `dev` scripts and a committed `package-lock.json`.
- `vite.config.js` registering only the Laravel plugin and Tailwind plugin.
- Minimal CSS/JS entry files under `resources/`.
- npm build and dependency audit steps in CI.

The following remain frozen until this ADR is Accepted:

- Any UI framework or component strategy (Livewire, Inertia, React, Vue, Alpine, or similar).
- Application views, screens, routes or interactive behaviour.
- UI component libraries, remote fonts, themes or design systems.
- New frontend dependencies that are not required by the minimal build above.

`concurrently` and `@laravel/multiplex` were removed during Sprint 0.1 because they were unused by any committed script or source file. Remote font registration was removed because it is a design choice and an external network dependency, not required build infrastructure. No UI functionality was removed.

## Options

| Option | Strengths | Costs / risks |
| --- | --- | --- |
| Blade + Livewire + Alpine + Tailwind | Server-rendered, minimal JavaScript bundle, familiar to Laravel contributors, simple self-hosting | Complex interactive screens can become chatty; needs discipline on component boundaries |
| Inertia + React (or Vue) + Tailwind | Rich client interaction, component ecosystem, SPA-like feel | Node build pipeline, larger bundle, more concepts for contributors |
| Separate SPA + JSON API | Clear client/server separation | Two deployables, CORS/auth complexity, contradicts the monolith decision in ADR-0001 |

## Recommendation (not yet accepted)

Start with **Blade + Livewire + Alpine + Tailwind**. It best matches low-bandwidth use and a Laravel-first contributor base. Keep the option to introduce Inertia for a specific high-interaction area if evidence shows Livewire requests are a bottleneck; that would require a new ADR.

## Evidence required before acceptance

1. Prototype one representative read-heavy screen and one form-heavy screen (in a later sprint, not Sprint 0) and record payload size and interaction latency.
2. Confirm contributor availability/familiarity.
3. Confirm the asset build works in Docker and CI. The npm build is verified in CI; host Node commands are documented in `docs/development/setup.md`.

## Consequences of deferral

UI-specific tooling and application UI are deliberately absent from Sprint 0. The minimal build infrastructure listed above is present but does not settle the UI framework decision. Do not add a JS framework, component library, application view or UI dependency until this ADR is Accepted.

## Review trigger

Revisit before the first application screen is implemented, or when evidence from a prototype contradicts the recommendation.
