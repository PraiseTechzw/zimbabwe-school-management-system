# Zimbabwe School Management System

An open-source, Zimbabwe-first school management platform. **Sprint 0 status: engineering foundation only.** No school features (admissions, attendance, fees, learner records, assessment, reporting, HR, boarding) are implemented in this repository yet.

## Principles

- Zimbabwe-first education software
- Research-driven, evidence-based decisions
- Modular architecture without unnecessary microservices
- Maintainable code, automated testing, security and privacy by design
- Strict scope management and open-source collaboration
- Self-hosted deployment capability

## Current state

| Area | Status |
| --- | --- |
| Application stack | Laravel 13 / PHP 8.3 (decided, ADR-0001) |
| Database strategy | MySQL 8 for dev/self-hosting, SQLite for CI (decided, ADR-0002) |
| Frontend approach | Under research (ADR-0003) — no UI implemented |
| Deployment/backups | Documented strategy (ADR-0004) |
| Docker development | Available via `docker compose` |
| CI | GitHub Actions: tests, formatting, static analysis, dependency audit |

## Quick start (Docker — reproducible environment)

```bash
git clone https://github.com/PraiseTechzw/zimbabwe-school-management-system.git
cd zimbabwe-school-management-system
cp .env.example .env
make up
make shell
php artisan migrate
```

Application: <http://localhost:8000>. MySQL is exposed on `localhost:3307` for development only.

Local (non-Docker) commands are documented in [docs/development/setup.md](docs/development/setup.md). Docker is the source of truth; the host PHP installation may not have all required extensions.

## Verification commands

```bash
make test       # Pest test suite (SQLite in memory)
make lint       # Laravel Pint check
make analyse    # PHPStan + Larastan
make audit      # Composer dependency security audit
```

## Documentation

- [Documentation index](docs/README.md)
- [Contributing](CONTRIBUTING.md)
- [Security policy](SECURITY.md)
- [Agent execution and reporting protocol](AGENTS.md)

## License

MIT — see [LICENSE](LICENSE).
