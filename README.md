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
| Frontend approach | UI framework under research (ADR-0003); minimal Vite/Tailwind build infrastructure only — no UI implemented |
| Deployment/backups | Documented strategy (ADR-0004); no production deployment configured |
| Docker development | Available via `docker compose`; **DEVELOPMENT ONLY**, never use as production |
| CI | GitHub Actions: tests, formatting, static analysis, Composer audit, npm audit and asset build |

## Quick start (Docker — reproducible environment)

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

Commands after `make shell` run inside the application container. Application: <http://localhost:8000>. MySQL is bound to `127.0.0.1:3307` for local development only. **The committed Compose stack is a development stack and must not be used as a production deployment.**

Local (non-Docker) commands are documented in [docs/development/setup.md](docs/development/setup.md). Docker is the source of truth; the host PHP installation may not have all required extensions. npm asset commands run on the host with Node 24 and are not executed inside the PHP application container.

## Verification commands

```bash
make test       # Pest test suite (SQLite in memory)
make lint       # Laravel Pint check
make analyse    # PHPStan + Larastan
make audit      # Composer dependency security audit
make assets     # Host Node 24: npm ci + npm run build
make npm-audit  # Host Node 24: npm audit --audit-level=high
```

Production requirements are documented in [deployment](docs/operations/deployment.md). No production stack, TLS endpoint, backup job or release tag exists in Sprint 0.

## Documentation

- [Documentation index](docs/README.md)
- [Contributing](CONTRIBUTING.md)
- [Security policy](SECURITY.md)
- [Agent execution and reporting protocol](AGENTS.md)

## License

MIT — see [LICENSE](LICENSE).
