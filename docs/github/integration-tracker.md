# Phase 1 integration tracker

**Verified:** 2026-11-27 (post academic-structure, frontend-contract and B-04 audit pass). **Scope:** current `origin/main` and all shipped platform-foundation/frontend PRs. This record distinguishes local evidence from GitHub-verified remote state; a local pass is never reported as remote CI success.

## Current remote baseline

- `origin/main`: `6844b4289af6c5446b80d897f73de39d4bb6aa7b` — merge of PR #61 (final academic-structure integration evidence); it contains PRs #56–#60. Earlier baselines were `c21d0a90530cc2764853e6b3eabb277501da696b` and `fd3a6b875c1a5cfcf850f1f0229a64e8b540a0e0`.
- Branch protection on `main`: required checks `quality`, `tests`, `docker`, strict; required conversation resolution enabled; required approving reviews `0`; `enforce_admins` enabled.
- Remote reads used: `git ls-remote`, `git fetch --prune`, `gh pr view`, `gh pr checks`, `gh api .../check-runs`, GraphQL review-thread queries, `gh project item-list`.

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
| Post-merge tracker correction | — / [#54](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/54) | head `bc6ad65`; merge `28079f5` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, CodeRabbit, GitGuardian SUCCESS | No approval claimed | Yes, `2026-11-27T18:11:05Z` | Not on board |
| Stage B calendar administration | — / [#56](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/56) | head `cec1f03`; merge `a466d17` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit pending at final read | Not recorded here | Yes, `2026-11-27T18:28:54Z` | Not on board |
| Deterministic frontend test fix | — / [#57](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/57) | head `93c6610`; merge `cd87d8a` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit pending at final read | Not recorded here | Yes, `2026-11-27T18:32:35Z` | Not on board |
| Documentation follow-up | — / [#58](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/58) | head `f301648`; merge `1c4d808` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit pending at final read | Not recorded here | Yes, `2026-11-27T18:56:07Z` | Not on board |
| Academic structure integration | — / [#59](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/59) | head `9fc4804`; merge `c21d0a9` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit pending at final read | No review threads or approvals recorded | Yes, `2026-11-27T19:01:30Z` | Not on board |
| Frontend academic-structure contracts | — / [#60](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/60) | head `17125df`; merge `fd3a6b8` | Yes | Yes | Yes | Opened | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit pending at final read | Not recorded here | Yes, `2026-11-27T19:25:27Z` | Not on board |
| Final academic-structure integration evidence | — / [#61](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/61) | merge `6844b42` | Yes | Yes | Yes | Opened | Remote result not re-checked for this audit | No approval claimed | Yes, `2026-11-27T19:33:02Z` | Not on board |
| B-04 authorization / RBAC / tenant audit | — / `audit/b04-security-rbac-isolation` | audit branch at `6844b42` | Yes | Yes | Yes | Opened for human review | Not checked; no remote CI claim | Pending | No | Not on board |
| Admissions application foundation (canonical) | — / none | local `8a7a21d404f627430dce0d9b7e8d852f2045ed02` | Yes | Yes | No | No | Not applicable | No | No | Not on board |

## Local verification evidence

All entries below are local runs, not GitHub Actions results.

| Worktree | Gates | Result |
| --- | --- | --- |
| `p1-roles-permissions` at `3d591d0` | `make verify COMPOSE="docker compose -p p1-roles"` | Exit 0; Pint, PHPStan, Composer audit, 156 tests / 555 assertions |
| `p1-audit-trail` at final `1154c3b` | `make verify COMPOSE="docker compose -p p1-audit-trail"` | Exit 0; Pint, PHPStan, Composer audit, 170 tests / 638 assertions |
| `p1-frontend-pr48` at `588ddd1` | Pint, PHPStan, Composer audit, `php artisan test`, `npm ci`, `npm run build`, `npm audit --audit-level=high`, `docker compose config -q`, `git diff --check` | All exit 0; 180 tests / 675 assertions |
| `p1-academic-structure-canonical` at `d137030` (held lineage) | Pint, PHPStan, Composer audit, structure Pest suite, full suite, `migrate:fresh`, `docker compose config -q`, `git diff --check` | All exit 0; structure 41 tests / 243 assertions; full suite 211 tests / 881 assertions |
| `p1-admissions-canonical` at `8a7a21d` | Pint, PHPStan, Composer audit, `php artisan test`, `migrate:fresh`, route list, `docker compose config -q`, `git diff --check` | All exit 0; 212 tests / 790 assertions |
| `p1-integration-tracker` after merged `main` | `make verify` via isolated Compose project and `docker compose config -q` | Exit 0; 180 tests / 675 assertions |
| `p1-academic-structure-integration` at merged head `9fc4804` | `make verify` via isolated Compose project, focused suites, MySQL 8.0.46 `migrate:fresh` / `migrate:status` / `migrate:rollback`, `docker compose config -q`, `git diff --check` | All exit 0; Pint 207 files clean; PHPStan no errors; Composer audit clean; 243 tests / 990 assertions; focused API 24 / 110, isolation 11 / 110, permissions 6 / 15, audit 2 / 14, schema 7 / 33 |
| `p1-academic-structure-tracker` docs branch, reconciled with `origin/main` `fd3a6b8` | `make verify` via isolated Compose project, focused permission suite, `docker compose config -q`, `git diff --check` | All exit 0; Pint 207 files clean; PHPStan no errors; Composer audit clean; 244 tests / 1007 assertions; permission suite 7 / 32 after the all-route regression test |
| B-04 clean audit worktree at `6844b42` | `make verify` via isolated Compose project, focused security suite, route-list JSON comparison, MySQL 8.0.46 `migrate:fresh`, `docker compose config -q`, `git diff --check` | All exit 0; Pint 209 files clean; PHPStan no errors; Composer audit clean; full suite 262 tests / 1134 assertions; focused suite 209 / 942; routes 19 → 29 permission-gated; MySQL migrations clean |

Logs are retained under `/tmp/opencode/` with the prefixes `roles-integration-verify`, `audit-secret-fix-verify`, `frontend-pr48-review`, `structure-canonical-final`, `admissions-canonical`, `integration-docs-verify`, `integration-docs-postmerge-verify`, `academic-verify` and `academic-tracker-verify`. B-04 evidence is retained locally in the audit worktree (`verify-final.log`, `mysql-migrate.log`) and under `/home/praisetechzw/.local/share/opencode/worktree/b04-route-evidence/` (`routes-baseline-6844b42.json`, `routes-fixed.json`).

## Dependency and duplication notes

- PRs #51, #49, #50, #48, #52, #54, #56, #57, #58, #59, #60 and #61 merged in that order. Frontend head `588ddd1` was merged as `9f8f712`; the tracker corrections followed as `64cf7e8` and `28079f5`; academic structure was merged as `c21d0a9`; frontend contract documentation was merged as `fd3a6b8`; final integration evidence was merged as `6844b42`. PR #60 landed while the docs/test follow-up branch was in review; the branch was reconciled by merging `main` into it without rebase or force-push. The B-04 audit branch is a new PR for human review; it is not an implementation deliverable and is not merged.
- The audit trail was reconciled with merged roles work and its inert redaction fixtures were rewritten so GitGuardian scans only the clean single commit; the final check result is SUCCESS.
- Academic structure had two competing local lines. Canonical lineage is `d602e88` replayed to `d137030`; the stale `77d2f9a` line duplicated merged school/calendar/auth work and was excluded. PR #59 replayed only the additive canonical implementation onto then-current `main`, then integrated the existing RBAC and audit foundations and short MySQL-safe index names. The held canonical branch is retained for lineage only; the merged `9fc4804` line is the single implementation.
- Admissions has two local lines. Canonical is the clean `3ade1f3` commit replayed onto the then-current `main` as `8a7a21d`; the other worktree remains uncommitted and is not eligible for integration. The canonical replay removes the apparent school-settings duplication caused by the old `9e6db32` base. It is behind current `main` and must be replayed again if authorized.
- Admissions tenant isolation is covered by `AdmissionsIsolationTest.php`: client `school_id` is prohibited, `X-School-Id` only selects authorised memberships, foreign applications return 404, cross-school academic-year references return a generic 422, and all writes resolve the school server-side.
- No duplicate remote branch or PR exists for academic structure or admissions. Admissions evidence is tracked as research issue #53; the admissions canonical branch remains local pending an explicit scope decision.

## Review findings and known limitations

- Earlier review claims that all registered HTTP routes carried `EnsurePermission` were inaccurate at the merged baseline. The B-04 route inventory found 50 routes (42 application-defined), with 19 permission-gated before the audit and 29 after it. Ten calendar/frontend routes gained gates in this branch. Remaining ungated application routes are school context, School Settings, dashboard/profile/module-notice reads and authentication endpoints; School Settings has no permission catalogue entry and the context/identifier permissions are unused. Decisions D-01 and D-02 in the [B-04 audit report](../reports/b04-authorization-rbac-tenant-audit.md) record the policy choices. Tenant-scoped reads/writes, cross-school ID rejection, relationship ownership, IDOR, direct API access and audit-on-write have code and test evidence; no application-layer cross-school bypass was demonstrated.
- The audit also closed the Livewire write re-check gap, fail-closed authority for suspended/disabled accounts, and client `school_id` shape validation across 20 validation points. Domain actions and `FormRequest::authorize()` still rely on middleware or the central permission service rather than independent per-action authorization, so future non-HTTP callers need their own contract (D-11).
- A read-only independent challenge found no confirmed defects in the patch. It raised stale Livewire snapshot reads after permission revocation, header-based school switching and domain-level actor re-checks; these are recorded as D-09, D-10 and D-11 in the [B-04 audit report](../reports/b04-authorization-rbac-tenant-audit.md). Wildcard admin policy remains D-04.
- Database review found the migration order, composite foreign keys, uniqueness and MySQL 8 index names sound at merged head. Outstanding limitations are recorded in the module documentation: the MySQL 64-character index-name fix is not pinned by an automated schema test; a partial rollback of the academic-years key while class offerings exist is not supported; no MySQL concurrency or lock-contention test was run; hard cascades require restore row-count checks.
- Documentation review found unrelated governance/status drift outside this task group (ADR-0003 versus `AGENTS.md`, D-07 versus git workflow/project-board approval settings, and stale audit/auth/settings status wording). It is not changed in this docs correction and remains a separate governance decision.

## Remaining actions

1. No feature implementation PRs remain open. PR #61 is merged. The B-04 audit PR is open for human review and is not merged; its local checks are recorded above, and no remote CI result is claimed. Required checks and branch protection were satisfied for every merge listed above.
2. Board items #34–#40 are Done. Research items #6–#24 and #53 and governance items #31/#32 remain open and are not implementation deliverables.
3. Academic structure is merged; do not replay or re-open the held canonical line. Admissions remains local-only pending an explicit scope decision.
4. Do not push directly to `main`; do not merge unverified work.
