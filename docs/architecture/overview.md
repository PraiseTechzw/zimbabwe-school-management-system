# Architecture overview

## Shape

One Laravel application, one database, one deployable unit. See [ADR-0001](../adr/0001-stack-and-modular-architecture.md) and [ADR-0002](../adr/0002-database-and-test-data.md).

```
Browser ──▶ nginx ──▶ PHP-FPM (Laravel) ──▶ MySQL 8
                         │
                         └──▶ storage/ (uploads, caches, logs)
```

## Code layout

| Path | Purpose |
| --- | --- |
| `app/Http/` | Thin controllers, middleware, requests, route-facing concerns |
| `app/Domains/<Context>/` | Domain logic owned by one bounded context (actions, models, services) |
| `app/Support/` | Cross-cutting technical helpers with no domain meaning |
| `database/migrations/` | Schema history; one source of truth for structure |
| `resources/` | Minimal Vite/Tailwind asset inputs only; UI framework decision pending in ADR-0003 |
| `routes/` | Route definitions only; no business logic |
| `tests/Unit`, `tests/Feature` | Pest tests; feature tests use SQLite in memory |

A domain owns its tables and public actions. Other domains call those actions or explicit service interfaces rather than reaching into another domain's models. No domain is implemented in Sprint 0.

## Request flow

1. Route → middleware (auth, school context, throttle, locale) → controller.
2. Controller validates via a Form Request and delegates to a domain action.
3. Action performs domain work inside a transaction where needed.
4. Response is rendered as JSON or a Blade view.

## School context (Phase 1, provisional)

HTTP reads of school-owned data are scoped by an authenticated, server-side school context. `ResolveSchoolContext` derives the current school from the user's `school_user` memberships; a client-supplied school ID can only select among those memberships and can never grant access to another school. School-owned models expose **opt-in** `forSchool()` and `forCurrentSchool()` scopes; the latter fails closed when no context was authorised. There is no global scope: unscoped Eloquent queries are not automatically tenant-filtered, so every new school-owned read must explicitly use the appropriate scope. Domain actions are internal and must be given an explicit, authorised school before being exposed over HTTP.

This is a reversible shared-database boundary, not a final tenancy topology. ADR-0001 and ADR-0002 remain authoritative; a separate ADR is required before committing to a final topology.

## Explicitly out of scope in Sprint 0

Admissions, attendance, fees, student/learner records, assessment, reporting, HR, boarding, and any other school functionality. Infrastructure code, documentation, templates and baseline tests only.
