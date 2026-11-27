# B-05 — Audit trail coverage review

**Status:** executed on `origin/main`, implementation open for human review.
**Baseline SHA:** `6844b4289af6c5446b80d897f73de39d4bb6aa7b` (`origin/main`, merge of PR #61).
**Review branch:** `audit/b05-audit-trail-coverage`, created from the verified baseline in an isolated worktree.
**B-04 baseline:** PR #63 `fix(auth): close authorization and tenant isolation gaps`, head `7567ebc7bc09aaee3fb25de413566626b0fb9510`, state `OPEN`, base `main`, mergeable, all checks passing. It is **not** part of the audited baseline and no B-04 work is merged by this task.

This is a coverage review plus narrowly scoped implementation. It does not create a compliance claim, retention policy, role taxonomy or product requirement where none exists. Existing documented requirements and new proposals are separated below.

## 1. Verified baseline and method

Commands run from `/home/praisetechzw/dev/projects/zimbabwe-school-management-system`:

```text
git fetch origin --prune
git rev-parse origin/main                  -> 6844b4289af6c5446b80d897f73de39d4bb6aa7b
git log --oneline -6 origin/main           -> 6844b42 (#61), fd3a6b8 (#60), c21d0a9 (#59), ...
gh pr view 63 --json ...                   -> OPEN, MERGEABLE, head 7567ebc, mergedAt null
gh pr checks 63                            -> quality, tests, docker, GitGuardian, CodeRabbit: pass
```

The isolated worktree was created with `git worktree add -b audit/b05-audit-trail-coverage ... origin/main` and verified at `6844b42`. No work was done directly on `main`; no reset, clean, rebase, force-push or merge was performed. The frontend agent's worktree (`feature/frontend-visible-ui`) was checked and left untouched.

Route counts were derived from `php artisan route:list --json` on the baseline and on PR #63 head:

| Source | Registered routes | Application route objects | Vendor/framework objects | Application method–path bindings | `EnsurePermission` route objects |
| --- | --- | --- | --- | --- | --- |
| Baseline `6844b42` | 50 | 42 | 8 | 73 | 19 |
| PR #63 head `7567ebc` (unmerged) | 50 | 42 | 8 | 73 | 29 |

The 73 count expands Laravel aliases (`GET|HEAD` = 2 methods, `PUT|PATCH` = 2). It is a derived count, not a literal string in repository docs. These are authorization-gate counts, not audit-event counts.

## 2. Operation inventory

### 2.1 Existing documented audit requirement boundary

The audit foundation (`docs/modules/platform-foundation/audit-trail.md`, `docs/modules/platform-foundation/decision-log.md` decisions D-01/D-09/Slice 7) documents deliberate audit events for administrative/system events, with academic calendar configuration writes as the integration pattern. It explicitly excludes admissions, learner, finance, welfare, staff, governance workflows, authentication changes, roles/permissions, automatic request logging, retention/legal hold, SIEM export and a user interface.

`docs/research/non-functional-requirements.md` records NFR-007 (personal-data access, permission changes, privileged/financial actions) as **PROPOSED** pending U-04. `docs/modules/platform-foundation/roles-and-permissions.md` defers permission-change audit to an owner decision. These are proposals, not approved requirements.

### 2.2 Implemented write operations on the baseline

| Area | Operations | Audit state before B-05 |
| --- | --- | --- |
| Academic calendar | create/update academic year; create/update academic period | 4 deliberate events in category `school_configuration` |
| Academic structure | create/update educational level, grade level, class definition, stream, class offering | 10 deliberate events in category `school_configuration` |
| School settings | first save and subsequent update of the school settings record | No #40 event; provenance revisions only (`school_setting_revisions`) |
| Authorization | HTTP assign/remove school role; CLI `roles:assign` bootstrap | No event; B-04 D-05/D-06 open |
| School identity / membership | school creation, identifier/status writes, membership attach/detach, tenant context switching | No event; no approved audit rule found |
| Authentication | login, logout, `/auth/me`, failed attempts | Explicitly excluded from audit foundation scope |
| Frontend / Livewire | calendar administration writes call the same domain actions; settings/role components call domain gateways | Writes inherit domain audit; Livewire reads are a separate B-04 D-09 concern |
| Financial | none implemented in Sprint 0 / Phase 1 foundation | No financial operation to audit; no requirement invented |
| Sensitive-data access | school/identifier reads, audit query action | No access-event audit; NFR-007 is PROPOSED only |
| Delete/archive | no HTTP delete/archive API for calendar or structure; school deletion is not an exposed workflow | No event; out of scope |

The 42 application route objects include reads and framework-adjacent endpoints; the write inventory above is the meaningful denominator for deliberate audit coverage, not raw route count.

## 3. Existing audit architecture — verified facts

| Property | Finding | Evidence |
| --- | --- | --- |
| Single writer | `RecordAuditEvent` → `AuditEventRecorder::record()` is the documented supported append path | `app/Domains/AuditTrail/Services/AuditEventRecorder.php:19-67`; `app/Domains/AuditTrail/Actions/RecordAuditEvent.php` |
| Schema | `audit_events` has `occurred_at`, `created_at` but no `updated_at`; JSON `metadata`/`changes`; four school-scoped indexes | `database/migrations/2026_11_27_000020_create_audit_events_table.php:13-36` |
| Append-only | Model `updating`/`deleting` hooks throw; builder blocks mutation methods; no HTTP write/delete route | `app/Domains/AuditTrail/Models/AuditEvent.php:66-83`; `ImmutableAuditEventBuilder.php`; `tests/Feature/Domains/AuditTrail/AuditTrailTest.php` |
| Tenant scope | `AuditScope::forSchool()` requires a resolved `School`; `system()` is the only null-school path; query action always filters by school | `AuditScope.php`; `app/Domains/AuditTrail/Actions/QueryAuditEvents.php:19-36`; `AuditTrailIsolationTest.php` |
| Actor | Explicit actor wins; otherwise `Auth::user()` → user id, else `system`. No names/emails/tokens stored | `AuditEventRecorder.php:31,69-74`; `Support/AuthenticatedActorResolver.php`; `AuditActor.php` |
| Source/correlation | Allow-listed `domain|http|cli|job|import`; correlation header allow-list and format validation | `config/audit.php:24-30,43-46,65-71`; `Support/RequestCorrelationResolver.php` |
| Payload safety | Recursive key redaction to `[REDACTED]`; objects, oversized structures and invalid keys rejected; domain snapshots select named scalar fields and replace structure JSON with a presence flag | `Support/SanitizeAuditData.php`; `app/Domains/AcademicStructure/Concerns/RecordsAcademicStructureAudit.php:57-123`; `tests/.../AuditTrailTest.php:90-150` |
| Timestamps | `occurred_at` caller-supplied or now; `created_at` server now; both immutable casts | `AuditEventRecorder.php:45-62`; `AuditEvent.php:54-59` |
| Transactions | Recorder opens no transaction; all 14 baseline call sites write after the domain save inside the caller's transaction, so audit failure rolls the domain write back | Calendar/structure action files; `docs/.../audit-trail.md:52-58` |
| Tests | 16 baseline audit tests across recorder/redaction/mutation, isolation, migration and structure integration | `tests/Feature/Domains/AuditTrail/*`, `tests/Feature/Domains/AcademicStructure/AcademicStructureAuditTest.php` |
| Known boundary | Raw `DB::table('audit_events')` and privileged DB roles bypass Eloquent protections; no retention, legal hold, hash chain or DB trigger | `docs/.../audit-trail.md:74,115-122` |

## 4. Coverage matrix

Legend: **Policy** = supported by the existing audit foundation decision; **Proposed** = candidate only, no approved requirement; **Excluded** = documented out of scope.

| Operation | Module | Audit required? | Existing audit event | Actor | Tenant | Payload safe | Test | Gap |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Create academic year | AcademicCalendar | Policy — yes | `academic_year.created` | Auth user or explicit actor | Server-resolved `School` | Named scalar metadata + before/after | `AcademicCalendarTest`, `AuditTrailIsolationTest` | None |
| Update academic year | AcademicCalendar | Policy — yes | `academic_year.updated` | Same | Same | Same | Same | None |
| Create academic period | AcademicCalendar | Policy — yes | `academic_period.created` | Same | Same | Same | Same | None |
| Update academic period | AcademicCalendar | Policy — yes | `academic_period.updated` | Same | Same | Same | Same | None |
| Create/update educational level | AcademicStructure | Policy — yes | `educational_level.created/updated` | Same | Same | Structure JSON replaced with `configuration_present` | `AcademicStructureAuditTest` | None |
| Create/update grade level | AcademicStructure | Policy — yes | `grade_level.created/updated` | Same | Same | Named scalars | Same | None |
| Create/update class definition | AcademicStructure | Policy — yes | `class_definition.created/updated` | Same | Same | Presence flag for config | Same | None |
| Create/update stream | AcademicStructure | Policy — yes | `stream.created/updated` | Same | Same | Named scalars | Same | None |
| Create/update class offering | AcademicStructure | Policy — yes | `class_offering.created/updated` | Same | Same | Presence flag for config | Same | None |
| First settings save | SchoolSettings | Policy — yes (B-05 integration) | `school_settings.created` (added) | Auth user via resolver | Locked server-resolved `School` | Existing `snapshot()` shape; no `school_id` | `SchoolSettingsAuditTest` (new) | None; revisions remain separate provenance |
| Settings update | SchoolSettings | Policy — yes (B-05 integration) | `school_settings.updated` (added) | Same | Same | Before/after snapshots, no tenant id | Same | None |
| Rejected settings write | SchoolSettings | Policy — no event expected | None | n/a | n/a | n/a | `SchoolSettingsAuditTest` (new) | None |
| Assign school role (HTTP) | Authorization | Proposed — D-05 open | None | Would be controller actor | Would be `SchoolContext` | Privacy-safe fields undecided | `RolesPermissionsTest` covers authorization, not audit | **Policy decision required** |
| Remove school role (HTTP) | Authorization | Proposed — D-05 open | None | Same | Same | Undecided | Same | **Policy decision required** |
| CLI role bootstrap | Authorization | Proposed — D-06 open | None | Would need explicit `system`/`cli` actor | Server-resolved school | Undecided | Console test covers `source=cli`, not audit | **Policy decision required** |
| Login/logout/failed auth | Auth | Excluded by foundation scope | None | n/a | n/a | n/a | `AuthenticationTest` covers behavior, not audit | Deliberately out of scope |
| School create/identity/status writes | School | Proposed — no approved rule | None | n/a | n/a | n/a | `SchoolIdentityTest` | No requirement; not invented |
| School membership attach/detach | School / Authorization | Proposed — no approved rule | None | n/a | n/a | n/a | Isolation tests cover access, not audit | No requirement; not invented |
| Tenant switching via `X-School-Id`/session | School / HTTP | Proposed — D-10 open | None | n/a | Server-resolved context in existing events | n/a | `SchoolIsolationTest`, `SchoolSettingsIsolationTest` | Header policy unresolved; audit events already ignore client selector |
| Livewire calendar write | Frontend / AcademicCalendar | Policy — inherits domain write | Same calendar event | Auth user | Server-resolved school | Same | `CalendarAdministrationTest` | D-09 covers stale reads, not audit |
| Livewire stale read after revocation | Frontend | Proposed — D-09 open | Not an audit event | n/a | n/a | n/a | Baseline frontend tests | **Policy decision required** |
| Settings/Structure domain actor recheck | Domain | Proposed — D-11 open | Existing events record the actor but do not re-authorize | Auth user | Server-resolved school | Same | Permission tests cover HTTP routes | Non-HTTP caller contract unresolved |
| Audit query access | AuditTrail | Proposed — NFR-007 | None | n/a | Query filters by school | n/a | `AuditTrailIsolationTest` | No HTTP endpoint by design |
| Delete/archive operations | Calendar/Structure | Out of scope — no exposed API | None | n/a | n/a | n/a | Cascade behavior documented | Do not invent delete events |
| Financial operations | None implemented | Out of scope | None | n/a | n/a | n/a | n/a | No financial module in scope |

## 5. Reconciliation with B-04 (PR #63, D-01–D-11)

PR #63 changes 34 files (+899/−45): permission middleware on 10 route objects, Livewire write rechecks, `SchoolPermissions` fail-closed on non-active accounts, 20 `school_id` validation rules changed from `prohibited` to `missing`, two new security test files, five extended test files and the B-04 report. It does **not** change settings permissions, role/CLI audit, migrations, persistent middleware or the tenant-context resolver.

| Decision | B-04 status | Audit-coverage relevance | B-05 action |
| --- | --- | --- | --- |
| D-01 Settings permission catalogue | Open | Adding `settings.*` would be a new role contract | Not implemented; B-05 adds an audit event without a permission |
| D-02 Unused `school.view`/`school_identifiers.view` | Open | No enforced access event to key from | Left open |
| D-03 `school_editor` label narrower than grants | Open | Role metadata would be misleading | Left open |
| D-04 Wildcard `school_admin` | Open / characterized | Any new `audit.*` permission would be auto-granted | No new permission added |
| D-05 Role-change audit | Open | Direct audit gap | Not implemented; owner decision required |
| D-06 CLI role assignment audit | Open | Direct audit gap | Not implemented; owner decision required |
| D-07 Default provisioning role | Open | Actor authority for new users undefined | Left open |
| D-08 Composite academic-period FK | Open | Data-integrity, not audit | Left open |
| D-09 Livewire read recheck | Open | Stale reads expose data; writes are covered in PR #63 | Left open; B-05 does not duplicate PR #63 |
| D-10 Header-based tenant switching | Open | Audit events must never use the client selector | B-05 events use server-resolved `School`; policy unchanged |
| D-11 Domain-level actor authorization | Open | Non-HTTP callers not re-authorized | Left open |

Risk register: B04-F01..F05 (calendar permission boundary, Livewire write bypass, stale session, ambiguous tenant input, missing regressions) are fixed/tested in PR #63 only. B04-O01..O11 above remain open on both `6844b42` and PR #63 head. No B-04 risk is marked resolved by B-05.

## 6. Confirmed gaps and fixes implemented

### 6.1 Gap G1 — append-only builder bypass (confirmed, fixed)

`ImmutableAuditEventBuilder` blocked `update`, increment/decrement helpers and `delete`, but inherited `updateOrInsert`, `upsert` and `truncate` from Eloquent/Query Builder. Those paths could modify or erase audit rows without firing model events.

Fix: overrides in `app/Domains/AuditTrail/Models/ImmutableAuditEventBuilder.php` throw `LogicException` for all three. Regression assertions added to `tests/Feature/Domains/AuditTrail/AuditTrailTest.php`. Verified on SQLite (test suite) and MySQL 8.0.46 (tinker probe below).

### 6.2 Gap G2 — school settings writes had no #40 audit event (confirmed, fixed)

Before B-05, `UpdateSchoolSettings` wrote a settings row and revision but no `AuditEvent`. The foundation documents deliberate configuration writes as the integration pattern, and B-05 explicitly lists settings changes; no new permission, role or business rule is needed to record them.

Fix: new concern `app/Domains/SchoolSettings/Concerns/RecordsSchoolSettingsAudit.php`, called inside `UpdateSchoolSettings`'s existing transaction after the settings row is saved. It records `school_settings.created` on first save and `school_settings.updated` thereafter, with category `school_configuration`, source `domain`, `resource_type=school_setting`, the server-resolved school, the authenticated actor from the existing resolver, and before/after snapshots from the domain's `snapshot()` helper. No request body, tenant id, secret-like field or model-wide attribute dump is stored. Settings routes and permissions are unchanged (D-01 untouched).

### 6.3 Gap G3 — no transaction-rollback audit assertion (confirmed, fixed by test)

Existing rollback tests checked only domain rows. `SchoolSettingsAuditTest` now asserts that a forced outer-transaction failure removes the settings row, its revision and its audit event together.

### 6.4 Remaining gaps (not implemented — policy or owner decision required)

- Authentication, role assignment/removal, CLI bootstrap and permission-change events (D-05/D-06; foundation scope exclusion; NFR-007 PROPOSED).
- Settings permission gate, wildcard-role policy, role taxonomy cleanup (D-01/D-03/D-04).
- Livewire stale reads and persistent permission middleware (D-09).
- Header-based tenant-switching policy and audit of switching itself (D-10).
- Domain-level actor recheck for non-HTTP callers (D-11).
- Retention, legal hold, archival, hash-chain/tamper evidence, DB triggers and read UI/API authorization model.
- Value-level secret scanning: redaction is key-based by documented design; changing the safety floor needs operator review.
- `upsert`/`truncate` protection is Eloquent-level; raw SQL and privileged DB roles still bypass it.
- MySQL 64-character index-name fix and `lockForUpdate()` concurrency remain untested by automated schema/concurrency tests.

## 7. Verification evidence

### 7.1 SQLite / full suite (local, not GitHub Actions)

Command:

```text
make verify COMPOSE="docker compose -p b05-audit-coverage -f docker-compose.yml \
  -f /home/praisetechzw/.local/share/opencode/b05-audit-coverage-compose.override.yaml"
```

Results:

- Exit `0`.
- Pint `--test`: **209 files**, PASS.
- PHPStan/Larastan: `[OK] No errors`.
- `composer audit --locked`: `No security vulnerability advisories found.`
- Pest: **248 passed (1034 assertions)**.
- Focused `SchoolSettingsAuditTest` + `AuditTrailTest`: **11 passed (72 assertions)**.
- `docker compose config -q`: exit `0`.
- `git diff --check`: exit `0`.

### 7.2 MySQL 8.0.46 (isolated Compose stack, separately reported)

The stack used project `b05-audit-coverage`, ports `8025`/`3325` and subnet `10.99.25.0/24`. An initial health-check failure was caused by a missing application `.env` and un-migrated `sessions` table in the fresh bind-mounted worktree; after `cp -n .env.example .env`, `key:generate` and `php artisan migrate --force`, app, MySQL and nginx were healthy (`http_status=200`).

- `php artisan migrate:fresh --force --no-interaction`: exit `0`; all migrations including `2026_11_28_000005_create_class_offerings_table` DONE.
- `php artisan migrate:status`: exit `0`; migrations shown as `[1] Ran`.
- Append-only probe on MySQL via `php artisan tinker`: created `audit_events` row id `1`; `upsert`, `updateOrInsert` and `truncate` each threw `LogicException` with the expected messages; `mysql_count=1` afterwards.
- Settings audit probe on MySQL: `school_settings.created` written with `actor=user:1`, `school_id` matching the server-resolved school, and `changes.after.version=1`.

No claim is made that the Pest suite ran against MySQL: `phpunit.xml` forces SQLite in-memory with `DB_CONNECTION=sqlite`/`DB_DATABASE=:memory:`. MySQL evidence is migration plus direct runtime probes only.

## 8. Coverage percentage (methodologically scoped)

A single “percentage of all security-sensitive operations” would mix documented requirements with unresolved proposals, so none is claimed as a compliance figure. The defensible calculation is:

| Denominator | Covered | Percentage |
| --- | --- | --- |
| Deliberate configuration write actions following the documented #40 pattern (4 calendar + 10 structure + 1 settings) | 15 | **100%** |
| Those 15 plus the 3 authority-changing writes with an open audit decision (HTTP role assign, HTTP role remove, CLI bootstrap) | 15 of 18 | 83.3% if the proposed rule is assumed; **not claimed as a compliance figure** |
| Application route objects with `EnsurePermission` (authorization, not audit) | 19/42 on baseline; 29/42 on PR #63 head | 45.2% / 69.0%; context only |

## 9. Documentation updated

- This report: inventory, architecture findings, coverage matrix, B-04 reconciliation, gaps, fixes, evidence and decisions.
- `docs/modules/platform-foundation/audit-trail.md`: settings integration, expanded append-only list, B-05 limitation note.
- `docs/modules/platform-foundation/school-settings.md`: revisions versus #40 events, A-02 and #40 notes.
- `docs/modules/platform-foundation/decision-log.md`: B-05 integration record; D-01–D-11 untouched.
- `docs/github/integration-tracker.md`: verified baseline, PR #61 merge, open PR #63, B-05 verification row and PR.
- `docs/README.md`: report link.

## 10. Remaining risks and next backend task

Risks: policy-gated audit gaps in section 6.4 remain; B-04 is unmerged and may alter actor/permission paths when a human merges it; value-level redaction is key-based; raw SQL bypasses Eloquent immutability; MySQL concurrency is not covered.

Recommended next backend task: **B-06 — owner decision on role-change and CLI audit events (B-04 D-05/D-06)**, followed by implementation only if the Product Owner/security owner approves the event schema and actor semantics. Do not implement settings permissions, wildcard-role changes or tenant-switch policy without that approval.

## 11. Change identity

- Branch: `audit/b05-audit-trail-coverage`.
- Commits: three conventional commits on the branch — append-only builder fix, settings audit feature, coverage documentation. Final SHAs are recorded in the task handoff rather than in this self-referential file.
- PR: pending (filled after push).
