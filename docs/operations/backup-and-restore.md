# Backup and restore

Status: **documented procedures only**. No backup job, schedule, off-site destination or restore drill was configured or executed in Sprint 0 or Sprint 0.1.

School data is sensitive. Backups are part of the definition of done for any real deployment, but the commands below are examples for an operator to adapt and test in an isolated environment.

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

## Credential handling

Do **not** pass database passwords as process arguments (`-p"$MYSQL_ROOT_PASSWORD"`). Arguments are visible in process lists and may be captured in shell history or logs.

Use a short-lived MySQL options file with mode `0600`, read from the operator's secret store, and remove it after use:

```bash
umask 077
read -rsp 'MySQL root password: ' MYSQL_ROOT_PASSWORD; echo
printf '[client]\nuser=root\npassword=%s\n' "$MYSQL_ROOT_PASSWORD" > "$HOME/.zsm-mysql-client.cnf"
docker compose cp "$HOME/.zsm-mysql-client.cnf" mysql:/tmp/zsm-client.cnf
```

The password is held in a shell variable and a protected file, not in a command argument. Keep the file out of version control and delete it after the operation.

## Documented backup command (not executed in Sprint 0.1)

```bash
docker compose exec -T mysql \
  mysqldump --defaults-extra-file=/tmp/zsm-client.cnf \
  --single-transaction --routines --triggers school > backup.sql

docker compose exec -T mysql rm -f /tmp/zsm-client.cnf
rm -f "$HOME/.zsm-mysql-client.cnf"
unset MYSQL_ROOT_PASSWORD

# Encrypt before storing off-host.
gpg --encrypt --recipient "$BACKUP_RECIPIENT" backup.sql
```

## Documented restore procedure (not executed in Sprint 0.1)

Run only in a disposable environment with the same application version. Require a second operator to review the command before any production restore.

```bash
umask 077
read -rsp 'MySQL root password: ' MYSQL_ROOT_PASSWORD; echo
printf '[client]\nuser=root\npassword=%s\n' "$MYSQL_ROOT_PASSWORD" > "$HOME/.zsm-mysql-client.cnf"
docker compose cp "$HOME/.zsm-mysql-client.cnf" mysql:/tmp/zsm-client.cnf

docker compose exec -T mysql \
  mysql --defaults-extra-file=/tmp/zsm-client.cnf school < backup.sql

docker compose exec -T mysql rm -f /tmp/zsm-client.cnf
rm -f "$HOME/.zsm-mysql-client.cnf"
unset MYSQL_ROOT_PASSWORD

docker compose exec app php artisan migrate --force
docker compose exec app php artisan test
```

Verify row counts and a known record before declaring success. Never restore into production without a tested restore path and a second operator reviewing the command.

## Verification status

The safer command pattern above was reviewed for credential exposure but **was not executed** during Sprint 0 or Sprint 0.1. It must be tested in an isolated restore drill before being relied upon for production. See the Sprint 0.1 verification report for the checks that were actually executed.
