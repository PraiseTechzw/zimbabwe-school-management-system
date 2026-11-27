---
description: Run and report the full local verification loop
---

Run the full verification loop for this repository and report each command with its exit code:

1. `docker compose config -q`
2. `make verify` (Pint, PHPStan/Larastan, Composer security audit, Pest suite)

If a command cannot run, state why; never guess a result. Distinguish these local results from remote GitHub Actions checks. Focus on: $ARGUMENTS
