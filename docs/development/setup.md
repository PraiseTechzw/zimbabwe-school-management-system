# Environment setup

Docker Compose is the reproducible source of truth. The host machine may lack PHP extensions; use Docker for all application commands when in doubt.

## Docker workflow (recommended)

Prerequisites: Docker Engine 29+ with Compose v2, Git, `make`.

```bash
git clone https://github.com/PraiseTechzw/zimbabwe-school-management-system.git
cd zimbabwe-school-management-system
cp .env.example .env
make up
make shell
php artisan key:generate
php artisan migrate
exit
make test
```

Commands after `make shell` run inside the application container. The committed Compose stack is labelled and documented as a **DEVELOPMENT** stack; it is not a production deployment and must not be exposed beyond the local machine.

- Application: <http://localhost:8000>
- MySQL: `127.0.0.1:3307` (development only). The port is bound to the loopback interface. Credentials are injected into the app container by Docker Compose from `DB_USERNAME` / `DB_PASSWORD` shell variables, defaulting to local-only development values. They are deliberately absent from `.env.example`.
- `make help` lists all targets; `make verify` runs lint, static analysis, Composer audit and tests.
- `make down` stops containers but preserves the MySQL named volume. `make fresh` **destroys** development data.

The app container runs `composer install` from `composer.lock` on start, so dependency changes require `make up`/rebuild, not manual host installs. The PHP image intentionally does not contain Node; asset commands are host-side commands documented below and are verified by CI.

## Local host workflow (documented, not the source of truth)

On this development host PHP 8.3 is present but `pdo_mysql` is not. Install extensions before running Laravel locally:

```bash
sudo apt install php8.3-mysql php8.3-gd php8.3-intl php8.3-zip php8.3-bcmath
composer install
cp .env.example .env
php artisan key:generate
```

`composer setup` installs Composer dependencies, creates `.env` if absent and generates `APP_KEY`. It deliberately does **not** run migrations: local database configuration varies, and forcing a MySQL migration before credentials are configured caused silent setup failures. Configure a database first, then run `php artisan migrate` explicitly.

For a SQLite-only local database, set `DB_CONNECTION=sqlite`, create `database/database.sqlite` and run `php artisan migrate`. To use MySQL locally, point `DB_HOST` at a MySQL 8 instance and add your own `DB_USERNAME` / `DB_PASSWORD` to the gitignored `.env` — these two keys are intentionally absent from `.env.example` so no credential-shaped value is committed.

Local quality commands:

```bash
php artisan test
composer lint
composer analyse
composer security-audit
```

Asset build commands require Node 24 on the host (the PHP application container does not include Node). These commands run outside Docker:

```bash
npm ci
npm run build
npm audit --audit-level=high
```

## Frontend development

Stage A provides three working screens behind the existing session middleware: `/login`, `/app`, `/app/school` and `/app/academic-calendar` (plus honest "not built yet" notices for planned modules). Sign-in and sign-out call the documented `/api/auth/login` and `/api/auth/logout` endpoints; server-rendered screens read school, identifier and calendar data through `app/Frontend/ServerFrontendDataProvider`, which mirrors the verified JSON payloads without extra HTTP round trips.

Real data is always authoritative. The dashboard's cross-module figures are opt-in demo values controlled by `FRONTEND_DEMO` in `.env`; the committed default is `false`. When enabled for a local demo, the UI labels them `Demo data`. Never enable demo mode in a deployment that presents figures as school records.

To run the full Docker quality gates against a worktree while the normal development stack is still running, give Compose a separate project name and start only the app service:

```bash
docker compose -p school-fe-check up -d --build --no-deps app
make COMPOSE="docker compose -p school-fe-check" verify
docker compose -p school-fe-check down
```

ADR-0003 was accepted on 2026-11-27. The Vite/Laravel plugin/Tailwind build is now part of the approved Blade + Livewire + Alpine + Tailwind frontend; Livewire is installed through Composer, not Node. See `docs/frontend/progress.md` for verification evidence and `DESIGN.md` for the visual system.

## Environment variables

`.env.example` is committed and contains **no credential values**. `.env` is gitignored. Test encryption keys are generated at runtime in `tests/bootstrap.php`, never committed. Production values are injected by the operator's environment/secret store; see [deployment](../operations/deployment.md).

## Troubleshooting

| Symptom | Check |
| --- | --- |
| `No application encryption key` | Run `php artisan key:generate` in the app container after copying `.env.example`. |
| nginx reports unhealthy immediately after `make up` | Expected on a fresh `.env` with an empty `APP_KEY`: the healthcheck probes `/`, which returns 500 until the key is generated. Generate the key with `php artisan key:generate`; health recovers without recreating containers. |
| MySQL healthcheck never passes | Inspect `docker compose logs mysql`; confirm no local service occupies port 3307. |
| Permission errors writing `storage/` | Confirm host UID/GID matches the container `user` setting (`${UID:-1000}`). |
