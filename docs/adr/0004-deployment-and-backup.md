# ADR-0004 — Deployment and backup

- Status: **Accepted as a strategy; operational targets still require stakeholder confirmation**
- Date: 2026-11-27
- Updated: 2026-11-27 (Sprint 0.1 — development/production boundary clarified)

## Context

The system must be self-hostable, with no dependency on a single commercial cloud. Sprint 0 provides Docker Compose for development and documents a deployment path; it does not configure a production host or continuous deployment.

## Decision

- The committed Docker Compose stack is a **DEVELOPMENT** stack only. It must not be used as a production deployment because it exposes MySQL on loopback with development credential defaults, enables `APP_DEBUG`, runs Composer with dev dependencies and binds only HTTP.
- Production uses one application and one database, built from the same released source commit, with an operator-managed runtime configuration and environment-specific values supplied from a secret store. No production Compose file, image variant or automated deployment is added in Sprint 0.
- Production requirements are mandatory before real data is stored: `APP_DEBUG=false`, HTTPS at an operator-managed reverse proxy, `SESSION_SECURE_COOKIE=true`, secrets injected from a secret store (never committed), `composer install --no-dev` in the production image/artifact, hardened database credentials, and a verified backup/restore path.
- TLS terminates at a reverse proxy managed by the operator (for example Caddy or an existing Nginx). Application containers are not publicly exposed except through the proxy.
- Releases are tagged commits. Deployment runs production dependency installation, migrations, cache/route/config refresh, then switches traffic. Rollback = previous release plus a verified database restore point.
- Backups: nightly logical MySQL dump plus application storage files; encrypted off-site copy; retention and restore drill described in [backup and restore](../operations/backup-and-restore.md).
- No automated production deployment is configured in Sprint 0.

## Alternatives considered

| Alternative | Reason not chosen |
| --- | --- |
| Managed PaaS / serverless | Conflicts with self-hosted capability and introduces vendor-specific runtime assumptions. |
| Kubernetes | Unjustified complexity for one application and small school deployments. |
| No documented backup strategy | Unacceptable for student and staff data. |

## Consequences

Operators need Docker and basic Linux skills. Zero-downtime migrations are not solved; schema changes must remain backward-compatible across one release (expand/contract). Recovery point/objective targets are proposals until confirmed by stakeholders.

## Review trigger

Revisit when a real deployment host is chosen, when multi-school tenancy changes the backup unit, or when RPO/RTO targets are agreed.
