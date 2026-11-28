# Phase 1 integration tracker

**Verified:** 2026-11-28 (reconciled after PR #69 merged and B-04 follow-up PR #72 was updated). **Scope:** current `origin/main` and all shipped platform-foundation/frontend PRs. This record distinguishes local evidence from GitHub-verified remote state; a local pass is never reported as remote CI success.

## Current remote baseline

- `origin/main`: `7de682c739ac49de5d8259732ad423fe27ef1365` — merge of PR #69 (frontend academic-structure screen); contains PRs #56–#69 except the open B-06/B-07/learner lines and the open B-04 follow-up. Prior baselines in this reconciliation were `81cba1d` (PR #68), `40d6c0f` (PR #67), `5e5c726` (PR #66), `6f2d868` (PR #63), `9f84acb` (PR #64), `bb79498` (PR #65), `98e8226` (PR #62), and `6844b42` (PR #61).
- B-04 audited baseline: `c21d0a90530cc2764853e6b3eabb277501da696b` — merge of PR #59. The audit started while this was current `main`; the first remediation branch was reconciled onto `6844b42`, and PR #62 merged the calendar write-permission portion at `98e8226`.
- Earlier recorded baselines `28079f5b9f6dbb1173ffde1585eedfd84269fb3a` (PR #54) and `fd3a6b875c1a5cfcf850f1f0229a64e8b540a0e0` (PR #60) are superseded and retained only for historical traceability.
- Branch protection on `main`: required checks `quality`, `tests`, `docker`, strict; required conversation resolution enabled; required approving reviews `0`; `enforce_admins` enabled. This was verified earlier and is retained from the prior tracker record, not re-asserted for PR #72.
- Remote reads used for this reconciliation: `git ls-remote`, `git fetch --prune`, `gh pr list --state all`, and `gh pr view 72`. No `gh pr checks` result is claimed for PR #72.

## Delivery states

| Deliverable | Issue / PR | Head / merge commit | Implemented | Committed | Pushed | PR | Remote CI | Human review | Merged | Board |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| School identity | #34 / [#41](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/41) | — | Yes | Yes | Yes | Merged | Verified success | Not recorded here | Yes | Done |
| School isolation | #35 / [#43](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/43) | — | Yes | Yes | Yes | Merged | Verified success | Not recorded here | Yes | Done |
| Authentication/users | #36 / [#44](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/44) | — | Yes | Yes | Yes | Merged | Verified success | Not recorded here | Yes | Done |
| Roles/permissions | #37 / [#49](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/49) | head `3d591d0`; merge `3d60962` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS | No approval claimed | Yes, `2026-11-27T17:50:25Z` | Done |
| School configuration | #38 / [#51](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/51) | head `9e6db32`; merge `b738870` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS | No approval claimed | Yes, `2026-11-27T17:44:31Z` | Done |
| Academic calendar | #39 / [#45](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/45) | — | Yes | Yes | Yes | Merged | Verified success | Not recorded here | Yes, `2026-11-27T16:31:15Z` | Done |
| Audit trail | #40 / [#50](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/50) | head `1154c3b`; merge `ccb25a7` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS at final head | No approval claimed | Yes, `2026-11-27T17:53:39Z` | Done |
| Frontend Stage A | — / [#48](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/48) | head `588ddd1`; merge `9f8f712` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS at final head | Six automated threads resolved; no approval count required | Yes, `2026-11-27T18:03:31Z` | Not on board |
| Integration docs sync | — / [#52](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/52) | head `90b8330`; merge `64cf7e8` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS at final head | No approval claimed | Yes, `2026-11-27T18:06:43Z` | Not on board |
| Post-merge tracker correction | — / [#54](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/54) | head `bc6ad65`; merge `28079f5` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, CodeRabbit, GitGuardian SUCCESS | No approval claimed | Yes, `2026-11-27T18:11:05Z` | Not on board |
| Stage B calendar administration | — / [#56](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/56) | head `cec1f03`; merge `a466d17` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit pending at final read | Not recorded here | Yes, `2026-11-27T18:28:54Z` | Not on board |
| Deterministic frontend test fix | — / [#57](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/57) | head `93c6610`; merge `cd87d8a` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit pending at final read | Not recorded here | Yes, `2026-11-27T18:32:35Z` | Not on board |
| Documentation follow-up | — / [#58](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/58) | head `f301648`; merge `1c4d808` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit pending at final read | Not recorded here | Yes, `2026-11-27T18:56:07Z` | Not on board |
| Academic structure integration | — / [#59](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/59) | head `9fc4804`; merge `c21d0a9` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit pending at final read | No review threads or approvals recorded | Yes, `2026-11-27T19:01:30Z` | Not on board |
| Frontend academic-structure contracts | — / [#60](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/60) | head `17125df`; merge `fd3a6b8` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit pending at final read | Not recorded here | Yes, `2026-11-27T19:25:27Z` | Not on board |
| Academic-structure evidence follow-up | — / [#61](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/61) | head `3fdc0ca`; merge `6844b42` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit pending at final read | No approval claimed | Yes, `2026-11-27T19:33:02Z` | Not on board |
| B-04 calendar write permission fix | — / [#62](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/62) | head `c88be8d`; merge `98e8226` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian SUCCESS; CodeRabbit review completed | One minor thread resolved after review | Yes, `2026-11-27T21:01:25Z` | Not on board |
| B-04 authorization / RBAC / tenant audit | — / [#63](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/63) | head `3c78c23`; merge `6f2d868` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian, CodeRabbit SUCCESS after live-read fix | Two automated threads resolved after review | Yes, `2026-11-28T10:00:55Z` | Not on board |
| Frontend visible UI | — / [#64](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/64) | merge `9f84acb` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian, CodeRabbit SUCCESS | No unresolved review threads | Yes, `2026-11-27T21:21:58Z` | Not on board |
| B-05 audit trail coverage | — / [#65](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/65) | head `d15c926`; merge `bb79498` | Yes | Yes | Yes | Merged | `quality`, `tests`, `docker`, GitGuardian, CodeRabbit SUCCESS | One minor thread resolved after review | Yes, `2026-11-27T21:16:31Z` | Not on board |
| B-06 audit policy decision brief | — / [#66](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/66) | merge `5e5c726` | Yes | Yes | Yes | Merged | Verified at merge | No approval claimed | Yes, `2026-11-28T10:13:19Z` | Not on board |
| Academic-structure docs accuracy | — / [#67](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/67) | merge `40d6c0f` | Yes | Yes | Yes | Merged | Verified at merge | No approval claimed | Yes, `2026-11-28T10:21:32Z` | Not on board |
| Academic-structure coverage gaps | — / [#68](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/68) | merge `81cba1d` | Yes | Yes | Yes | Merged | Verified at merge | No approval claimed | Yes, `2026-11-28T10:30:40Z` | Not on board |
| Frontend academic-structure UI | — / [#69](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/69) | merge `7de682c` | Yes | Yes | Yes | Merged | Verified at merge | No approval claimed | Yes, `2026-11-28T10:41:32Z` | Not on board |
| B-07 domain architecture audit | — / [#70](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/70) | Open branch | — | — | — | OPEN | Not claimed | Not claimed | No | Not on board |
| Learner-management foundation | — / [#71](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/71) | Open branch | — | — | — | OPEN | Not claimed | Not claimed | No | Not on board |
| B-04 follow-up authorization gaps | — / [#72](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/72) | branch `audit/b04-f03-f07-followup` | Yes | Yes | Yes | **OPEN** | **Not claimed** | **Not claimed** | **No** | Not on board |
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
| `p1-academic-structure-integration` at merged head `9fc4804` | `make verify` via isolated Compose project, focused suites, MySQL 8.0.46 `migrate:fresh` / `migrate:status` / `migrate:rollback`, `docker compose config -q`, `git diff --check` | All exit 0; Pint 207 files clean; PHPStan no errors; Composer audit clean; 243 tests / 990 assertions |
| `p1-academic-structure-tracker` docs branch, reconciled with `origin/main` `fd3a6b8` | `make verify` via isolated Compose project, focused permission suite, `docker compose config -q`, `git diff --check` | All exit 0; Pint 207 files clean; PHPStan no errors; Composer audit clean; 244 tests / 1007 assertions |
| B-04 clean audit worktree at `6844b42` | `make verify` via isolated Compose project, focused security suite, route-list JSON comparison, MySQL 8.0.46 `migrate:fresh`, `docker compose config -q`, `git diff --check` | All exit 0; Pint 209 files clean; PHPStan no errors; Composer audit clean; 262 tests / 1134 assertions; focused 209 / 942; routes 19 → 29 permission-gated |
| `b05-audit-coverage` at `origin/main` `6844b42` plus B-05 fixes | `make verify`, focused audit/settings suites, MySQL 8.0.46 migration probes, `docker compose config -q`, `git diff --check` | All exit 0; Pint 209 files clean; PHPStan no errors; Composer audit clean; 248 tests / 1034 assertions; see `docs/reports/b05-audit-trail-coverage.md` |
| `b04-audit` at audited baseline `c21d0a9` | `make verify` | Exit 0; Pint, PHPStan, Composer audit; 243 tests / 990 assertions |
| `b04-audit` after authorization fixes and rebase onto `6844b42` | Focused security suites, `make verify`, route list, `docker compose config -q`, `git diff --check` | Exit 0; focused 164 / 671; full 264 / 1105 |
| `b04-security-audit` at reconciled merge head `27e8c70` | `make verify`, `docker compose config -q`, `git diff --check` | All exit 0; 274 tests / 1192 assertions |
| `b04-security-audit` at `fc0c816` plus CI-fix and review follow-ups | `make verify`, `docker compose config -q`, `git diff --check`, remote `gh pr checks` | Local all exit 0; 291 tests / 1280 assertions; remote `tests` initially failed on `ViteManifestNotFoundException`, fixed by `withoutVite()`; review findings fixed |
| `b06-audit-policy-decisions` after `6f2d868` | `make verify`, `docker compose config -q`, `git diff --check` | All exit 0; 291 tests / 1280 assertions |
| `b04-f03-f07-followup` at reconciled `origin/main` `7de682c` | `make verify`, focused security/frontend suites, route-list JSON, `docker compose config -q`, `git diff --check` | All exit 0; Pint 228 files clean; PHPStan no errors; Composer audit clean; **356 tests / 1443 assertions**; focused 73 / 190; routes 47 app / 39 permission gates / 2 tenant-storage guards. Evidence: `/home/praisetechzw/b04-audit-evidence/followup-make-verify.log`, `followup-focused-tests.log`, `followup-routes-app.json` |

Logs are retained under `/tmp/opencode/` with the earlier prefixes and under `/home/praisetechzw/b04-audit-evidence/` for B-04. The authoritative current report is [`docs/reports/b04-authorization-and-tenant-isolation-audit.md`](../reports/b04-authorization-and-tenant-isolation-audit.md).

## Dependency and duplication notes

- PRs #51, #49, #50, #48, #52, #54, #56, #57, #58, #59, #60, #61, #62, #65, #64, #63, #66, #67 and #68 merged in that order. All merges were verified with remote command output at the time; no shared history was force-pushed.
- The B-04 follow-up branch was created from the merged B-04 line, then reconciled by merging `origin/main` (`6f2d868`, then `5e5c726`, then `81cba1d`, then `7de682c`) into it. The PR #69 academic-structure route/navigation conflict was resolved by retaining its existing `academic_structure.view` gate and the B-04 settings/roles gates. PR #72 remains open; no rebase or force-push was used.
- PR #64 introduced the visible settings and role screens. Their original membership-only route registration is recorded as F10 in the B-04 follow-up report and fixed in PR #72.
- The frontend navigation catalogue now mirrors route permissions for presentation only. Route middleware and gateway/domain checks remain the authorization boundary.
- Academic structure had two competing local lines; canonical lineage is the merged `9fc4804` line. The held `d137030` line is retained for lineage only and must not be replayed.
- Admissions has two local lines; canonical is `8a7a21d`, which remains local-only pending an explicit scope decision. Its tenant isolation is covered by `AdmissionsIsolationTest.php`.
- No duplicate remote branch or PR exists for academic structure or admissions. PR #69 is merged; PRs #70–#71 remain open and are separate review lines. PR #72 is the open B-04 follow-up review line.

## Review findings and known limitations

- The older claim that all registered HTTP routes carried `EnsurePermission` was inaccurate at the merged baseline. The authoritative B-04 report now records **47 application-owned routes, 39 `EnsurePermission` gates, 2 tenant-signed storage guards, 1 membership-only no-data route, 2 auth-only routes, and 3 intentionally public routes**. See [`../reports/b04-authorization-and-tenant-isolation-audit.md`](../reports/b04-authorization-and-tenant-isolation-audit.md) for the full matrix and decisions.
- Tenant-scoped reads/writes, cross-school ID rejection, relationship ownership, IDOR, direct API access, role escalation, sensitive-data filtering, permission-aware navigation, and audit-on-write have code and test evidence; no application-layer cross-school bypass was demonstrated.
- Domain actions and FormRequests rely on the central permission service and route/domain double-checks; future non-HTTP callers must use `SchoolPermissions::authorize()` explicitly. MFA, separation of duties, time-bounded delegation, periodic review, and permission-change audit events remain deferred and require separate decisions.
- MySQL was unavailable in the B-04 follow-up environment; MySQL-specific integration is not claimed for PR #72. Earlier MySQL migration evidence for other PRs is retained above.
- No remote CI result, review approval, deployment, or merge is claimed for PR #72. Its recorded state at creation was `OPEN` with `mergedAt: null`; re-check before any merge decision.
- Database review found migration order, composite foreign keys, uniqueness and MySQL 8 index names sound at merged head. Outstanding limitations are in module documentation and remain separate from this authorization follow-up.
- Documentation review found unrelated governance/status drift outside this task group (ADR-0003 versus `AGENTS.md`, D-07 versus git workflow/project-board approval settings). It is not changed here.

## Remaining actions

1. PRs #62–#69 are merged. PRs #70, #71 and #72 are open. Do not merge PR #72 before review and remote check evidence exist.
2. Assign an explicit configured role to existing roleless school members before deployment (R-01 in the B-04 report). No schema migration is needed.
3. Board items #34–#40 are Done. Research items #6–#24 and #53 and governance items #31/#32 remain open and are not implementation deliverables.
4. Academic structure is merged; do not replay or re-open the held canonical line. Admissions remains local-only pending an explicit scope decision.
5. Do not push directly to `main`; do not merge unverified work.
