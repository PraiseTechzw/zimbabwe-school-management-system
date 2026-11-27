# Documentation index

Sprint 0 established the engineering foundation. Phase 1 school-scoped backend foundation is in progress; discovery documents remain historical/proposed where labelled, and the [Phase 1 decision log](modules/platform-foundation/decision-log.md) records implementation status.

## Architecture and decisions

- [Architecture overview](architecture/overview.md)
- [ADR process](adr/README.md)
- [ADR-0001 — Stack and modular architecture](adr/0001-stack-and-modular-architecture.md)
- [ADR-0002 — Database and test data](adr/0002-database-and-test-data.md)
- [ADR-0003 — Frontend strategy](adr/0003-frontend-strategy.md)
- [ADR-0004 — Deployment and backup](adr/0004-deployment-and-backup.md)

## Frontend (ADR-0003 accepted 2026-11-27)

- [Product intent](../PRODUCT.md) — audience, constraints and scope gate
- [Design system](../DESIGN.md) — tokens, components and visual rules
- [Frontend progress](frontend/progress.md) — module statuses, evidence and verification log
- [API dependency tracker](frontend/api-dependencies.md) — verified endpoints and provisional contracts

## Development

- [Environment setup](development/setup.md) — Docker and local host commands
- [Git workflow](development/git-workflow.md)
- [Engineering conventions](development/engineering-conventions.md)
- [Agent execution and reporting protocol](development/agent-workflow.md)

## Quality and operations

- [Testing strategy](testing/strategy.md)
- [School Settings API](api/school-settings.md) — school-scoped settings, provenance and revisions
- [Deployment strategy](operations/deployment.md)
- [Backup and restore](operations/backup-and-restore.md)
- [GitHub project board guidance](github/project-board.md)
- [Phase 1 integration tracker](github/integration-tracker.md) — verified PR, CI, board and merge states

## Sprint 0 report

- [Verification evidence](reports/sprint-0-verification.md) — actual command output and GitHub checks

## Phase 1 — Platform Foundation discovery (PROPOSED, not approved)

- [Phase 1 Discovery Report](modules/platform-foundation/discovery-report.md) — board, research and next task
- [Research index](research/README.md) — all discovery artefacts
- [Product discovery](modules/platform-foundation/product-discovery.md) — PROPOSED product definition
- [Phase 1 specification](modules/platform-foundation/phase-1-specification-proposed.md) — Product Owner approval gate `P1-G01`
- [Traceability matrix](modules/platform-foundation/traceability-matrix.md) — discovery-time links only; implementation status is recorded in the decision log
- [Phase 1 decision log](modules/platform-foundation/decision-log.md) — authoritative implementation status and decisions
- [Academic calendar](modules/platform-foundation/academic-calendar.md) — implemented backend slice
- [Auth and users](modules/platform-foundation/auth-user-foundation.md) — implemented backend slice
- [Roles and permissions](modules/platform-foundation/roles-and-permissions.md) — config-driven authorization foundation for #37
- [School settings](modules/platform-foundation/school-settings.md) — implemented backend settings mechanism for #38
- [Audit trail](modules/platform-foundation/audit-trail.md) — append-only, privacy-minimised audit foundation for #40
- [Research coverage matrix](research/research-coverage-matrix.md) — evidence classes and gaps

## Templates

- [Feature specification](templates/feature-specification.md)
- [Module specification](templates/module-specification.md)
- [Change request](templates/change-request.md)
