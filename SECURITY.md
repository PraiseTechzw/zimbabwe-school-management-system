# Security policy

## Supported versions

The repository is in Sprint 0 (foundation only). Security fixes are applied to `main`. No tagged releases exist yet; this policy applies to `main` until the first release tag is published.

## Reporting a vulnerability

Use GitHub's private vulnerability reporting on this repository (**Security → Report a vulnerability**). Include reproduction steps, impact and affected version. Do not post vulnerabilities, tokens, learner data or production dumps in public issues.

## Response expectations (proposed)

- Acknowledge within 5 working days.
- Triage severity and confirm reproduction before discussing details publicly.
- Fix, test and release; credit reporters who wish to be named.

## Project rules

- No secrets or personal data in version control; `.env` is gitignored.
- Validate input and authorise every action server-side.
- Log events, not sensitive payloads.
- `composer audit` and Dependabot run in CI; dependency PRs require review.
- Backup and restore expectations are documented in [backup and restore](docs/operations/backup-and-restore.md).

Sprint 0 did not perform a production security audit or configure a deployment host.
