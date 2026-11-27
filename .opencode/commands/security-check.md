---
description: Read-only security and privacy check of a file set
agent: security-reviewer
---

Security-check: $ARGUMENTS

Inspect `.env.example`, dependency advisories, validation/authorisation paths and any data-handling code in scope. Confirm no secrets or personal data are committed. Report severity-ranked findings with `file:line` or explicitly state that none were found. Do not edit files.
