# Phase 1 integration tracker

**Verified:** 2026-11-27 (final post-merge pass). **Scope:** current `origin/main` and all shipped platform-foundation/frontend PRs. This record distinguishes local evidence from GitHub-verified remote state; a local pass is never reported as remote CI success.

## Current remote baseline

- `origin/main`: `64cf7e84286234db464b6a2a2071333bfeb641cd` — merge of PR #52 (integration status sync), after PR #48 (frontend Stage A).
- Branch protection on `main`: required checks `quality`, `tests`, `docker`, strict; required conversation resolution enabled; required approving reviews `0`; `enforce_admins` enabled.
- Remote reads used: `git ls-remote`, `git fetch --prune`, `gh pr view`, `gh api .../check-runs`, `gh project item-list`.

## Delivery states

| Deliverable | Issue / PR | Head / merge commit | Implemented | Committed | Pushed | PR | Remote CI | Human review | Merged | Board |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| School identity | #34 / [#41](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/41) | — | Yes | Yes | Yes | Opened | Verified success | Not recorded here | Yes | Done |
| School isolation | #35 / [#43](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/43) | — | Yes | Yes | Yes | Opened | Verified success | Not recorded here | Yes | Done |
| Authentication/users | #36 / [#44](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/44) | — | Yes | Yes | Yes | Opened | Verified success | Not recorded here | Yes | Done |
| Roles/permissions | #37 / [#49](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/49) | head `3d591d0`; merge `3d60962` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS | No approval claimed | Yes, `2026-11-27T17:50:25Z` | Done |
| School configuration | #38 / [#51](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/51) | head `9e6db32`; merge `b738870` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS | No approval claimed | Yes, `2026-11-27T17:44:31Z` | Done |
| Academic calendar | #39 / [#45](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/45) | — | Yes | Yes | Yes | Opened | Verified success | Not recorded here | Yes, `2026-11-27T16:31:15Z` | Done |
| Audit trail | #40 / [#50](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/50) | head `1154c3b`; merge `ccb25a7` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS at final head | No approval claimed | Yes, `2026-11-27T17:53:39Z` | Done |
| Frontend Stage A | — / [#48](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/48) | head `588ddd1`; merge `9f8f712` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS at final head | Six review threads resolved after fixes were verified | Yes, `2026-11-27T18:03:33Z` | Not on board |
| Integration status docs | — / [#52](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/52) | head `90b8330`; merge `64cf7e8` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS | No approval claimed | Yes, `2026-11-27T18:06:45Z` | Not on board |
| Academic structure (canonical) | — / none | local `d1370300b066b97acfc8472666e0bc02efeac6fa` | Yes | Yes | No | No | Not applicable | No | No | Not on board |
| Admissions application foundation (canonical) | — / none | local `8a7a21d404f627430dce0d9b7e8d852f2045ed02` | Yes | Yes | No | No | Not applicable | No | No | Not on board |

## Local verification evidence

All entries below are local runs, not GitHub Actions results.

| Worktree | Gates | Result |
| --- | --- | --- |
| `p1-roles-permissions` at `3d591d0` | `make verify COMPOSE="docker compose -p p1-roles"` | Exit 0; Pint, PHPStan, Composer audit, 156 tests / 555 assertions |
| `p1-audit-trail` at final `1154c3b` | `make verify COMPOSE="docker compose -p p1-audit-trail"` | Exit 0; Pint, PHPStan, Composer audit, 170 tests / 638 assertions |
| `p1-frontend-pr48` at `588ddd1` | Pint, PHPStan, Composer audit, `php artisan test`, `npm ci`, `npm run build`, `npm audit --audit-level=high`, `docker compose config -q`, `git diff --check` | All exit 0; 180 tests / 675 assertions |
| `p1-academic-structure-canonical` at `d137030` | Pint, PHPStan, Composer audit, structure Pest suite, full suite, `migrate:fresh`, `docker compose config -q`, `git diff --check` | All exit 0; structure 41 tests / 243 assertions; full suite 211 tests / 881 assertions |
| `p1-admissions-canonical` at `8a7a21d` | Pint, PHPStan, Composer audit, `php artisan test`, `migrate:fresh`, route list, `docker compose config -q`, `git diff --check` | All exit 0; 212 tests / 790 assertions |

Logs are retained under `/tmp/opencode/` with the prefixes `roles-integration-verify`, `audit-secret-fix-verify`, `frontend-pr48-review`, `structure-canonical-final` and `admissions-canonical`.

## Dependency and duplication notes

- PRs #51, #49, #50, #48 and #52 merged in that order; no PR remains open. The frontend head was based on `ccb25a7`, so it contains the platform-foundation merges.
- The audit trail was reconciled with merged roles work and its inert redaction fixtures were rewritten so GitGuardian scans only the clean single commit; the final check result is SUCCESS.
- Academic structure has two competing local lines. Canonical is the `d602e88` line replayed onto current `main`; the stale `77d2f9a` line duplicates merged school/calendar/auth work and is excluded.
- Admissions has two local lines. Canonical is the clean `3ade1f3` commit replayed onto current `main` as `8a7a21d`; the other worktree remains uncommitted and is not eligible for integration. The canonical replay removes the apparent school-settings duplication caused by the old `9e6db32` base.
- Admissions tenant isolation is covered by `AdmissionsIsolationTest.php`: client `school_id` is prohibited, `X-School-Id` only selects authorised memberships, foreign applications return 404, cross-school academic-year references return a generic 422, and all writes resolve the school server-side.
- No duplicate remote branch or PR exists for academic structure or admissions. Admissions evidence is tracked as research issue #53; both canonical branches remain held pending an explicit scope decision.

## Remaining actions

1. No open PRs remain. `main` is green; required checks and branch protection were satisfied for every merge.
2. Board items #34–#40 are Done. Research items #6–#24 and #53 and governance items #31/#32 remain open and are not implementation deliverables.
3. Academic structure and admissions remain local-only pending an explicit scope decision. Research issue #53 records the admissions evidence question; do not push either canonical branch or open a PR without authorization.
4. Do not push directly to `main`; do not merge unverified work.
