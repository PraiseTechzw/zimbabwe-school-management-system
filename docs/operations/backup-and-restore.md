# Backup and restore

School data is sensitive. Backups are part of the definition of done for any deployment.

## What to back up

| Asset | Method | Notes |
| --- | --- | --- |
| MySQL database | `mysqldump --single-transaction --routines --triggers` | Nightly; encrypt before leaving the host |
| `storage/app` | File sync/archive | Uploaded documents; preserve permissions metadata |
| `.env` / secrets | Operator's secret store, not the backup archive | Never place secrets in an unencrypted dump |
| Application code | Git tags/releases | Rebuild from source, not from a container snapshot |

## Proposed schedule (requires stakeholder confirmation)

- Nightly logical database dump; retain 30 daily, 12 monthly copies.
- Off-site encrypted copy after each successful local dump.
- Quarterly restore drill into an isolated environment; record duration and result.
- Alert on backup failure; a missing backup is an incident.

## Restore drill (example)

```bash
# Inside a disposable environment with the same image version
docker compose exec -T mysql mysql -u root -p"$MYSQL_ROOT_PASSWORD" school < backup.sql
docker compose exec app php artisan migrate --force
docker compose exec app php artisan test
```

Verify row counts and a known record before declaring success. Never restore into production without a second operator reviewing the command.

## Sprint 0 status

Strategy documented; **no backup job, schedule or off-site destination was configured**. This is an outstanding manual step for the first real deployment.
