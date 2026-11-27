# AGENTS.md — project rules for every agent

This file applies to OpenCode, Copilot, Cursor, Codex and any other agent working **in this repository**. The workspace-level `~/AGENTS.md` (home directory) also applies; this file adds project-specific rules.

## Scope

- **Sprint 0 is engineering foundation only.** Do not implement admissions, attendance, fees, learner/student records, assessment, reporting, HR, boarding or any other school functionality.
- Do not add microservices, new frameworks or major dependencies without an accepted ADR in `docs/adr/`.
- Application UI and frontend architecture decisions are frozen until ADR-0003 is accepted. Only the minimal asset build infrastructure explicitly permitted by ADR-0003 may be maintained in Sprint 0; do not add UI frameworks, component libraries, application views or UI dependencies.

## How to work here

1. Consult the installed skills map (`~/.config/opencode/SKILLS-INDEX.md`) and load a matching skill before non-trivial work.
2. Work in **controlled, sequential task groups** with disjoint file sets. Never let agents edit overlapping files simultaneously.
3. Docker Compose is the reproducible environment. Run verification inside it when the change touches runtime behaviour; local host commands are documented in `docs/development/setup.md`.
4. Use Conventional Commits and short-lived branches. **Never push directly to `main`.** Open a PR and leave it for human review.
5. Never expose secrets. `.env` is local-only; `.env.example` holds placeholders. Do not print tokens, keys or personal data.
6. Do not claim external GitHub configuration, CI results or deployment success without command output proving it.

## Required verification before reporting done

```bash
make verify      # Pint, PHPStan/Larastan, Composer audit, Pest suite
docker compose config -q
```

Record commands, exit codes and output in the report. Distinguish local checks from remote GitHub Actions checks.

## Detailed protocol and templates

- [Agent execution and reporting protocol](docs/development/agent-workflow.md)
- [Git workflow](docs/development/git-workflow.md)
- [Engineering conventions](docs/development/engineering-conventions.md)
- [Feature / module / change templates](docs/README.md)
