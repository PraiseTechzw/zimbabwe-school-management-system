# PRODUCT.md — Zimbabwe school management system

Source of truth for product intent. Created 2026-11-27 from the explicit frontend brief and the Phase 1 discovery record; assumptions are labelled.

## What this is

An open-source, self-hosted school management system grounded in Zimbabwean school workflows. The Laravel monolith owns data, authorisation and tenancy. The UI is an internal working tool, not a public marketing site.

## Who uses it

- School administrators and office staff on shared desktops and low-cost phones.
- Teachers and heads entering or checking day-to-day information.
- Multi-campus staff with an explicitly selected school context.
- Parents and learners through separate responsive portals (later stages).

Assumption: contributors are Laravel-first with mixed frontend experience; no contributor survey was conducted (see ADR-0003 evidence item 2).

## Operating constraints (from the brief, not verified law)

- Low bandwidth and intermittent connectivity; every screen must be lightweight and usable on a phone.
- Shared devices: never leave sensitive learner, staff, welfare or financial data exposed on a general dashboard.
- Multi-currency and locally varied fee/governance practices must remain configurable, not hard-coded.
- MoPSE/ZIMSEC and SDC workflows matter, but no regulatory compliance claim may be made without an authoritative source.

## Product face

The product is a ledger of school records: ink figures on a quiet ground, one blue data hue for the current period, and the school's own name and identifiers visible in the shell. See `DESIGN.md`.

## Current scope gate

ADR-0003 was accepted on 2026-11-27. Stage A (architecture, design system, shell, navigation, dashboard, reusable components, mock adapter, API client) is in progress. School functional modules remain gated by their own approved specifications; see `docs/frontend/progress.md`.

## Screen jobs (Stage A)

1. Sign in with the existing session API, with explicit validation and error states.
2. Answer "which school am I working in, and what needs attention today?" from real auth/school endpoints; show clearly labelled demonstration values only where no endpoint exists.
3. Reach every planned module from a navigation that degrades cleanly on a phone and says plainly when a module has no UI yet.
