# Testing strategy

## Current baseline (Sprint 0)

- Framework: **Pest 4.7** with `pest-plugin-laravel`, PHPUnit 12.5.33 (pinned for Pest compatibility).
- Database: SQLite `:memory:` via `phpunit.xml`; no external service required.
- `.env.testing` is committed with test-only values and overrides `.env` when `APP_ENV=testing`. Docker Compose deliberately does **not** inject `.env` as process environment into the app container, so it cannot shadow the test configuration. DB credentials injected by Compose are ignored by the SQLite connection.
- `APP_KEY` is **not** stored in `.env.testing`, `phpunit.xml` or any committed file. `tests/bootstrap.php` generates a fresh 32-byte key on every run, so no key-shaped value can leak into version control or secret scanners.
- Tests present:
  - `tests/Unit/FoundationTest.php` — harness smoke test.
  - `tests/Feature/FoundationTest.php` — application boot, test DB config, framework welcome page.
- Commands: `composer test`, `make test` (inside Docker), `composer test -- --coverage` once a coverage driver is enabled.

## Test pyramid (for later sprints)

| Layer | Scope | Tooling |
| --- | --- | --- |
| Unit | Domain actions, value objects, policy logic with no framework I/O | Pest unit tests |
| Feature | HTTP requests, validation, authorisation, database effects | Pest feature tests + RefreshDatabase + factories |
| Integration | MySQL-specific behaviour, migrations, queue/backup paths | Dedicated job against MySQL in Docker (planned, not yet configured) |
| Browser | Critical user journeys only, after frontend ADR is accepted | Browser automation (planned) |

## Rules

- Every fix has a regression test; every new public action has a behaviour test.
- Prefer factories and explicit arrangement; no shared mutable fixtures across tests.
- Assert observable behaviour, not private implementation.
- Keep tests fast: no sleeps, no network calls, no real mail delivery.
- Coverage target is **proposed** at 80% for domain logic once domain code exists; it is not enforced in Sprint 0.

## CI gates

`quality` (validate, Pint, PHPStan/Larastan, Composer audit), `tests` (Pest) and `docker` (Compose config + image build) must pass before merge. Remote results are reported from GitHub Actions; local runs are labelled as local equivalents.

## Known gap

SQLite is not MySQL. Tests must avoid database-vendor-specific SQL, and a MySQL integration smoke job should be added before the first feature release.
