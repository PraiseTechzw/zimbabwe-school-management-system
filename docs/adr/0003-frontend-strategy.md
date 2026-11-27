# ADR-0003 — Frontend strategy

- Status: **Accepted — Blade + Livewire + Alpine + Tailwind (Product Owner decision, 2026-11-27)**
- Date: 2026-11-27
- Updated: 2026-11-27 (UI gate released; evidence conditions recorded below)

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
- Application views, screens, routes or interactive behaviour, except the default Laravel welcome page, its default route and the boot smoke test explicitly permitted below.
- UI component libraries, remote fonts, themes or design systems.
- New frontend dependencies that are not required by the minimal build above.

`concurrently` and `@laravel/multiplex` were removed during Sprint 0.1 because they were unused by any committed script or source file. Remote font registration was removed because it is a design choice and an external network dependency, not required build infrastructure. No UI functionality was removed.

Clarification: the default Laravel welcome page, its default route and the boot smoke test that asserts the framework welcome page are permitted foundation scaffolding. They are not approved application UI and do not constitute a frontend architecture decision; this ADR remains Proposed.

## Options

| Option | Strengths | Costs / risks |
| --- | --- | --- |
| Blade + Livewire + Alpine + Tailwind | Server-rendered, minimal JavaScript bundle, familiar to Laravel contributors, simple self-hosting | Complex interactive screens can become chatty; needs discipline on component boundaries |
| Inertia + React (or Vue) + Tailwind | Rich client interaction, component ecosystem, SPA-like feel | Node build pipeline, larger bundle, more concepts for contributors |
| Separate SPA + JSON API | Clear client/server separation | Two deployables, CORS/auth complexity, contradicts the monolith decision in ADR-0001 |

## Decision (accepted 2026-11-27)

Start with **Blade + Livewire + Alpine + Tailwind**. It best matches low-bandwidth use and a Laravel-first contributor base. Keep the option to introduce Inertia for a specific high-interaction area if evidence shows Livewire requests are a bottleneck; that would require a new ADR.

The Product Owner explicitly accepted this recommendation on 2026-11-27, releasing the UI freeze. This decision authorises UI frameworks, application views/routes and UI dependencies that follow this stack. It does not authorise school functionality that remains outside an approved delivery gate.

## Evidence record

1. **Prototype payload/latency evidence: outstanding, condition of acceptance.** The read-heavy screen (dashboard/school context) and form-heavy screen (sign-in) are the first Stage A deliverables. Their rendered payload sizes and interaction behaviour must be measured and recorded in `docs/frontend/progress.md` before Stage B begins. If the evidence contradicts this choice, reopen this ADR before adding Inertia.
2. **Contributor familiarity: assumed, not interviewed.** The Laravel-first contributor base is stated in the discovery record; no contributor survey was run. Recorded as an assumption rather than a verified requirement.
3. **Asset build: verified locally and wired in CI.** On 2026-11-27, `npm run build` exited 0 locally (three modules; CSS 55.21 kB / gzip 11.73 kB; JS 0.00 kB / gzip 0.02 kB) and `.github/workflows/ci.yml` runs `npm ci`, `npm run build` and `npm audit --audit-level=high`. Remote CI results are not claimed here.

## Consequences

Sprint 0 contained no application UI; this acceptance applies from Phase 1 onward. The minimal build infrastructure was retained. New UI work must use this stack, keep client JavaScript small, avoid remote font/network dependencies unless separately justified, and record integration status in `docs/frontend/progress.md`.

## Review trigger

Revisit when Stage A prototype measurements are recorded, when Livewire request chattiness becomes a measured bottleneck, or when a high-interaction area needs a different approach (new ADR required).
