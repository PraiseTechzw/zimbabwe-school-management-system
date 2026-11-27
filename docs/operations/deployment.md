# Deployment strategy

Status: documented strategy, **no production deployment configured in Sprint 0 or Sprint 0.1**. See [ADR-0004](../adr/0004-deployment-and-backup.md).

## Development stack warning

The committed `docker-compose.yml` and `docker/Dockerfile` are a **DEVELOPMENT** stack. They are labelled as such in Compose. **Do not use this configuration as a production deployment.** It deliberately:

- enables `APP_DEBUG=true` from `.env.example`;
- serves plain HTTP on `localhost:8000` with no TLS;
- binds MySQL to `127.0.0.1:3307` with local-only default credentials;
- installs Composer dev dependencies when the app container starts;
- has no backup job, secret store, production image hardening or monitoring.

## Reference production topology

```
Internet ──▶ TLS reverse proxy (operator-managed) ──▶ application HTTP ──▶ PHP-FPM/Laravel ──▶ MySQL 8
```

Production uses the same released source commit as CI, but an operator-managed runtime configuration. A production Compose file or image variant is **not** included in Sprint 0.1; adding one requires a specific follow-up decision and verification.

## Mandatory production requirements

Before real learner or staff data is stored, the operator must provide:

1. `APP_ENV=production` and `APP_DEBUG=false` from a secret store.
2. HTTPS terminated at an operator-managed reverse proxy; no plain-HTTP public listener.
3. `SESSION_SECURE_COOKIE=true`, a generated `APP_KEY`, and strong database credentials from the secret store.
4. A production PHP image/artifact built with `composer install --no-dev --optimize-autoloader`; the committed development image must not be promoted as-is.
5. A database backup and tested restore path (see [backup and restore](backup-and-restore.md)).
6. Confirmed retention, RPO/RTO and access-control policy.
7. Restricted network access to MySQL; it must not be publicly exposed.

None of these requirements were configured or tested in Sprint 0.1.

## Release procedure (manual; documented, not executed)

1. Select a tagged commit and confirm CI `quality`, `tests` and `docker` are green on that commit.
2. Take a verified database and storage backup (see [backup and restore](backup-and-restore.md)).
3. Build the production artifact with production dependencies only (`composer install --no-dev --optimize-autoloader`). Do not use the development Compose command.
4. Deploy environment-specific values from the operator's secret store; never commit `.env`.
5. Run migrations, then config/route/cache refresh using the production runtime.
6. Smoke test: HTTPS 200 on `/`, secure-session check, and health checks once features exist.
7. Record the release in `CHANGELOG.md` and the deployment log.

## Rollback

Stop the new release, restore the previous release artifact, and restore the database from the pre-deploy backup if migrations changed schema. Never roll back data without a tested restore.

## Constraints

- Backward-compatible migrations (expand/contract) across one release.
- Secrets injected via environment/secret store; `.env` never in Git or images.
- RPO/RTO targets are proposals pending stakeholder confirmation.
- Zero-downtime deployment is not solved in Sprint 0.

## Outstanding manual steps

Choose a host, configure TLS, provision secret storage, build a production image variant with dev dependencies excluded, schedule and test backups, and agree RPO/RTO. None of these were performed in Sprint 0 or Sprint 0.1.
