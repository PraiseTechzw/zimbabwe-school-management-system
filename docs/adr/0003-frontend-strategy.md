# ADR-0003 — Frontend strategy

- Status: **Proposed — final decision required before any UI implementation**
- Date: 2026-11-27

## Context

Sprint 0 must evaluate the frontend before implementation. Constraints: low-bandwidth and shared-device usage in some Zimbabwean schools, open-source contributors with mixed frontend experience, self-hosted deployment, and a small foundation team. No UI code is part of Sprint 0.

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
3. Confirm the asset build works in Docker and CI.

## Consequences of deferral

Frontend-specific tooling is deliberately absent from Sprint 0. Do not add a JS framework, component library, or asset pipeline until this ADR is accepted.
