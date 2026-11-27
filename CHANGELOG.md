# Changelog

All notable changes are documented here. Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- Sprint 0 engineering foundation: Laravel 13 application skeleton, Pest baseline tests, Pint and PHPStan/Larastan configuration.
- Docker Compose development stack (PHP-FPM 8.3, nginx, MySQL 8) with healthchecks and `make` targets.
- GitHub Actions CI (`quality`, `tests`, `docker`), Dependabot, issue templates, PR template, CODEOWNERS and label set.
- Architecture decision records, engineering conventions, testing/deployment/backup strategies and specification templates.
- OpenCode V2 specialist agents and reusable commands.
- Sprint 0.1 corrections: npm lockfile, npm build/audit in CI, npm Dependabot monitoring, nginx PHP handler hardening, loopback-bound MySQL port, and a documented runtime storage link.

### Changed

- No school functionality has been added.
- ADR-0003 now distinguishes minimal asset build infrastructure from the still-pending UI framework decision.
- The Compose stack is explicitly labelled and documented as DEVELOPMENT ONLY; production requirements are documented without adding a production stack.
- `composer setup` no longer runs a database migration before database configuration is verified.
- Read-only reviewer agents deny edit, shell, subagent delegation and Code Mode execution.

### Security

- Dependency audit configured for Composer and npm; no known vulnerabilities at verification time (see verification reports).
- Backup examples no longer place database passwords directly in process arguments.
