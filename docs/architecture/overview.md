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

1. Route → middleware (auth, throttle, locale) → controller.
2. Controller validates via a Form Request and delegates to a domain action.
3. Action performs domain work inside a transaction where needed.
4. Response is rendered as JSON or a Blade view.

## Explicitly out of scope in Sprint 0

Admissions, attendance, fees, student/learner records, assessment, reporting, HR, boarding, and any other school functionality. Infrastructure code, documentation, templates and baseline tests only.
