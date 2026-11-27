# Phase 1 integration tracker

**Verified:** 2026-11-27. **Scope:** current `origin/main` and the active platform-foundation PRs/branches. This record distinguishes local evidence from GitHub-verified remote state; a local pass is never reported as remote CI success.

## Current remote baseline

- `origin/main`: `64cf7e84286234db464b6a2a2071333bfeb641cd` — merge of PR #52 (integration docs sync) at `2026-11-27T18:06:43Z`; it contains the merged frontend Stage A commit `9f8f712`.
- Branch protection on `main`: required checks `quality`, `tests`, `docker`, strict; required conversation resolution enabled; required approving reviews `0`; `enforce_admins` enabled.
- Remote reads used: `git ls-remote`, `git fetch --prune`, `gh pr view`, `gh api .../check-runs`, GraphQL review-thread queries, `gh project item-list`.

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
| Frontend Stage A | — / [#48](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/48) | head `588ddd1`; merge `9f8f712` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS at final head | Six automated threads resolved; no approval count required | Yes, `2026-11-27T18:03:31Z` | Not on board |
| Integration docs sync | — / [#52](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/52) | head `90b8330`; merge `64cf7e8` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS at final head | No approval claimed | Yes, `2026-11-27T18:06:43Z` | Not on board |
| Post-merge tracker correction | — / this PR | branch `docs/p1-integration-status-postmerge` | Yes | Yes | Yes | Opened | Pending at this tracker commit | No approval claimed | No | Not on board |
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
| `p1-integration-tracker` after merging merged `main` | `make verify` via isolated Compose project and `docker compose config -q` | Exit 0; 180 tests / 675 assertions |

Logs are retained under `/tmp/opencode/` with the prefixes `roles-integration-verify`, `audit-secret-fix-verify`, `frontend-pr48-review`, `structure-canonical-final`, `admissions-canonical` and `integration-docs-verify`.

## Dependency and duplication notes

- PRs #51, #49, #50, #48 and #52 merged in that order; every later branch must contain all five merge commits. Frontend head `588ddd1` was merged into `main` as `9f8f712`; PR #52 was merged as `64cf7e8`.
- The audit trail was reconciled with merged roles work and its inert redaction fixtures were rewritten so GitGuardian scans only the clean single commit; the final check result is SUCCESS.
- Academic structure has two competing local lines. Canonical is the `d602e88` line replayed onto the then-current `main`; the stale `77d2f9a` line duplicates merged school/calendar/auth work and is excluded. That canonical branch is now two commits behind `64cf7e8` and must be replayed again if authorized.
- Admissions has two local lines. Canonical is the clean `3ade1f3` commit replayed onto the then-current `main` as `8a7a21d`; the other worktree remains uncommitted and is not eligible for integration. The canonical replay removes the apparent school-settings duplication caused by the old `9e6db32` base. It is also two commits behind `64cf7e8` and must be replayed again if authorized.
- Admissions tenant isolation is covered by `AdmissionsIsolationTest.php`: client `school_id` is prohibited, `X-School-Id` only selects authorised memberships, foreign applications return 404, cross-school academic-year references return a generic 422, and all writes resolve the school server-side.
- No duplicate open PR exists for academic structure or admissions. No admissions issue exists; both remain held pending an explicit scope decision.

## Remaining actions

1. Monitor this post-merge documentation correction. Merge it only after its CI is verified; no self-approval or remote merge is claimed here.
2. Record a dedicated scope decision/issue before pushing academic structure or admissions. Until then, keep both canonical branches local and open no PR.
3. If authorized, replay the two canonical branches onto current `main` (`64cf7e8` or later), re-run verification, then open one PR per slice and update this tracker with exact statuses.
4. Do not push directly to `main`; do not merge unverified work.
