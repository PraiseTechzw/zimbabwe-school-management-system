# Deployment strategy

Status: documented strategy, **no production deployment configured in Sprint 0**. See [ADR-0004](../adr/0004-deployment-and-backup.md).

## Reference topology

```
Internet ──▶ TLS reverse proxy (operator-managed) ──▶ nginx container ──▶ PHP-FPM app ──▶ MySQL 8 container
```

The Compose file in the repository is the development reference. Production uses the same service definitions with environment-specific values supplied at deploy time; do not commit a production Compose override containing credentials.

## Release procedure (manual until CD is approved)

1. Tag/commit selected; confirm CI `quality`, `tests` and `docker` are green on that commit.
2. Take a verified database backup (see [backup and restore](backup-and-restore.md)).
3. On the host: fetch the release, `cp .env.example .env` equivalent from the secret store, `docker compose build`, `docker compose up -d`.
4. Run `docker compose exec app php artisan migrate --force`, then config/route/cache refresh.
5. Smoke test: HTTP 200 on `/`, login/health checks once features exist.
6. Record the release in `CHANGELOG.md` and the deployment log.

## Rollback

Stop the new stack, restore the previous image tag, and restore the database from the pre-deploy backup if migrations changed schema. Never roll back data without a tested restore.

## Constraints

- Backward-compatible migrations (expand/contract) across one release.
- Secrets injected via environment/secret store; `.env` never in Git or images.
- RPO/RTO targets are proposals pending stakeholder confirmation.
- Zero-downtime deployment is not solved in Sprint 0.

## Outstanding manual steps

Choose a host, configure TLS, provision secret storage, schedule backups, and agree RPO/RTO. None of these were performed in Sprint 0.
