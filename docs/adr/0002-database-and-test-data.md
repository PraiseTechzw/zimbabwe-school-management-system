# ADR-0002 — Database and test data

- Status: **Accepted** (Sprint 0)
- Date: 2026-11-27

## Context

The system will be self-hosted by schools and therefore needs a database that is easy to provision and back up on modest infrastructure. The local host PHP has `pdo_sqlite` but not `pdo_mysql`; the Docker image installs `pdo_mysql`. Tests must be fast and deterministic in CI without an external database service.

## Decision

- **MySQL 8.0** is the system of record for development (Docker Compose) and self-hosted production.
- **SQLite `:memory:`** is used for the automated test suite, configured in `phpunit.xml`. Tests never depend on a running MySQL container.
- Schema changes go through **Laravel migrations** only. No hand-maintained SQL schema dumps in version control.
- Use foreign keys and transactions for data integrity. Keep domain-specific queries inside the owning domain's repositories/services.
- Development database credentials are injected by Docker Compose from operator/shell values with local-only defaults and are deliberately absent from `.env.example`. They must never be reused in production.

## Alternatives considered

| Alternative | Reason not chosen |
| --- | --- |
| PostgreSQL everywhere | Familiarity and hosting availability for self-hosted Zimbabwean deployments favour MySQL; revisit with evidence if needed. |
| SQLite in production | Concurrency and backup/restore semantics are weaker for multi-user school workloads. |
| MySQL for tests too | Slower CI, extra service orchestration, and the host currently lacks `pdo_mysql`. |

## Consequences

Positive: fast CI and simple local testing. Negative: SQLite/MySQL behavioural differences can hide bugs; domain code must avoid database-specific SQL and a future CI job should run a small smoke suite against MySQL before release.

## Verification / review trigger

Revisit when a feature relies on MySQL-specific behaviour (full-text search, generated columns, locking clauses) or when CI adds a MySQL integration job.

The Academic Structure foundation uses `lockForUpdate()` in `GuardsAcademicStructure`. SQLite tests do not prove concurrent MySQL lock behaviour; the manual MySQL 8 checks on PR #59 covered migrations only. See [Academic Structure](../../modules/academic-structure.md) for the recorded limitation.
