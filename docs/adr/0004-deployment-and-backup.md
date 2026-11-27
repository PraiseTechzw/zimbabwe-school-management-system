# ADR-0004 — Deployment and backup

- Status: **Accepted as a strategy; operational targets still require stakeholder confirmation**
- Date: 2026-11-27

## Context

The system must be self-hostable, with no dependency on a single commercial cloud. Sprint 0 provides Docker Compose for development and documents a deployment path; it does not configure a production host or continuous deployment.

## Decision

- **One Docker Compose stack** (app, nginx, MySQL) is the reference deployment unit. The same images used in development are promoted to production with environment-specific values from `.env`, never committed secrets.
- TLS terminates at a reverse proxy managed by the operator (for example Caddy or an existing Nginx). Application containers are not publicly exposed except through the proxy.
- Releases are tagged commits. Deployment runs `composer install --no-dev`, migrations, cache/route/config refresh, then switches traffic. Rollback = previous image tag plus a verified database restore point.
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
