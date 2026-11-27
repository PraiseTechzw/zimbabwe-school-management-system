# Agent execution and reporting protocol

This document applies to every AI agent working in this repository (OpenCode, Copilot, Cursor, Codex, and others). The root `AGENTS.md` remains authoritative for workspace-wide rules.

## Non-negotiable rules

1. **Sprint scope:** no school features in Sprint 0. Do not implement admissions, attendance, fees, learner records, assessment, reporting, HR, boarding, or related functionality.
2. **No silent architecture changes:** if a decision is hard to reverse, write or update an ADR and get approval before implementation.
3. **No secrets:** never read, print, copy or commit real credentials, tokens, keys or personal data. `.env` stays local; placeholders only in examples.
4. **PR-only:** never push directly to `main`. Agents push a task branch and open a PR; a human reviews and merges.
5. **Evidence:** do not claim a check, deployment or GitHub configuration is complete without command output proving it.

## Controlled task groups

Agents work **sequentially in small, disjoint groups**; they do not edit overlapping files at the same time. Each group follows this loop:

1. **Plan** — state the files and commands about to be touched and the expected verification.
2. **Implement** — change only that group's files.
3. **Verify** — run the relevant command and capture output (exit code included).
4. **Report** — summarise what changed, evidence, limitations and next group.

If two agents must work concurrently, assign non-overlapping file sets and merge through PRs. Read-only reviewer agents never edit.

## OpenCode configuration

- Agents: `.opencode/agents/*.md` (OpenCode V2 frontmatter: `description`, `mode`, optional `permissions`).
- Commands: `.opencode/commands/*.md` (OpenCode V2 frontmatter: `description`, `agent`, optional `model`/`subagent`; body is the prompt template; `$ARGUMENTS` supported).
- Agents are named around disciplines (architecture, backend, frontend, database, testing, security, DevOps, documentation, review) and are subagents by default.
- Commands wrap common workflows: specification, implementation planning, test writing, diff review, verification, security check, sprint reporting.

## Required report format

```text
Task group:
Files changed:
Commands run (with exit codes):
Result / evidence:
Risks or limitations:
Outstanding manual steps:
```

Never report "CI passed" from a local run alone; distinguish local equivalent checks from remote GitHub Actions results.
