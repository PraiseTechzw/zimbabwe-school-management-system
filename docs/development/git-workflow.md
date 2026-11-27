# Git workflow

## Branching

- `main` is protected: no direct pushes, no force pushes, no deletion.
- Work happens on short-lived branches created from `main`:
  - `feature/<short-name>`
  - `fix/<short-name>`
  - `chore/<short-name>`
  - `research/<short-name>`
  - `docs/<short-name>`
- Branch names use lowercase kebab-case. Keep branches focused; split unrelated work into separate PRs.

## Commits

Use Conventional Commits: `type(scope): summary` — for example `ci: add Pest and PHPStan workflow`. Types: `feat`, `fix`, `docs`, `test`, `chore`, `ci`, `refactor`, `research`. Imperative mood, no secrets, no generated vendor files.

## Pull requests

1. Push the branch; open a PR against `main` using [the PR template](../../.github/pull_request_template.md).
2. Link the issue(s) and state the evidence for the change.
3. Required checks: `quality`, `tests`, `docker`.
4. At least one approving review; squash-merge. Delete the branch after merge.
5. The author verifies the post-merge state; reviewers may request changes at any time.

The first Sprint 0 PR is opened by the setup agent and left for human review — it is **not** auto-merged.

## Change size and scope

- Keep diffs reviewable; infrastructure-only changes must not bundle feature code.
- Add or update tests and documentation in the same PR.
- Dependency updates require `composer audit` output in the PR description.
- Architectural changes require an ADR before implementation (see [ADR process](../adr/README.md)).

## Labels and board

Labels, issue templates and the project board convention are described in [project board guidance](../github/project-board.md). External configuration is verified with `gh` CLI rather than assumed.
