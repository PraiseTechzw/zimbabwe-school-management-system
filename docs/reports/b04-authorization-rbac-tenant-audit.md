# B-04 authorization, RBAC and tenant-isolation audit

Date: 2026-11-27. Verified baseline: `6844b4289af6c5446b80d897f73de39d4bb6aa7b` (`origin/main`, PR #61 merge). Audit branch: `audit/b04-security-rbac-isolation`.

Status: **complete for the current backend surface, with documented product/governance decisions left open**. No admissions routes or controllers exist at this baseline, so the Sprint 0 scope gate was respected. No external deployment or production data was touched.

All evidence below is local unless explicitly labelled as a GitHub read. Local test completion is not presented as GitHub Actions success.

## 1. Scope and method

- Confirmed the real remote baseline with `git fetch --prune origin`, `git ls-remote origin refs/heads/main` and `git log origin/main`. The originally reported `c21d0a9` was already superseded by PR #60 and then PR #61; the audit was rebased onto `6844b42` before any implementation.
- Inventoried **every registered route** with `php artisan route:list --json`: 50 total — 42 application-defined routes and 8 vendor/framework routes. No endpoint in this report was inferred from filenames.
- Read middleware, controllers, domain actions, requests, authorization config, Livewire components and migrations. Tested anonymous, authenticated, cross-school, role-restricted, suspended/disabled and client-controlled `school_id` paths.
- Fixed only defects with a demonstrated control gap or a concrete fail-open shape. Product and governance choices are recorded as unresolved decisions instead of being silently made.
- Kept the dirty concurrent audit worktree `/tmp/opencode/b04-authorization-audit` untouched. Its calendar/config changes were not reused. After the host restart removed writable `/tmp/opencode` worktrees, the clean audit branch was recreated under `/home/praisetechzw/.local/share/opencode/worktree/b04-security-audit` and the reviewed patch was reapplied there.

Route evidence files:

- Baseline: `/home/praisetechzw/.local/share/opencode/worktree/b04-route-evidence/routes-baseline-6844b42.json`
- Fixed: `/home/praisetechzw/.local/share/opencode/worktree/b04-route-evidence/routes-fixed.json`

## 2. Executive summary

| Measure | Baseline | After fixes |
| --- | --- | --- |
| Registered routes | 50 | 50 |
| Application-defined routes | 42 | 42 |
| Routes with `EnsurePermission` | 19 | 29 |
| Calendar API/frontend routes gated | 0 / 10 | 10 / 10 |
| Structure routes gated | 16 / 16 | 16 / 16 |
| Role-assignment routes gated | 3 / 3 | 3 / 3 |
| School Settings routes gated | 0 / 3 | 0 / 3 (catalogue decision open) |
| Full Pest suite (SQLite) | 243 passed / 990 assertions | 262 passed / 1134 assertions |

No cross-school data mutation, ownership bypass, IDOR or role-assignment boundary bypass was demonstrated in the current application layer. The confirmed defects were missing permission enforcement on calendar routes, a Livewire write path that did not replay the route gate, stale session authority after account suspension/disablement, and permissive client `school_id` validation that accepted `null`/empty shapes before the tenant guard.

## 3. Authorization coverage matrix

The matrix below is the post-fix audit snapshot: all 42 application-defined routes from the `route:list --json` output at that time are listed. “auth + school” means `auth` plus `school.context`/`ResolveSchoolContext` are present. The 8 remaining routes were vendor/framework routes (5 Livewire asset/upload routes, 2 framework storage routes and the health check); they are not application authorization boundaries. Current `origin/main` after the later frontend PR #64 has 52 registered routes (44 application-defined); the two additional application routes are `GET app/roles` and `GET app/school-settings`, both `auth + school` with no permission gate. They are recorded below for completeness; School Settings still has no catalogued permission (D-01).

| Method / path | Action | Baseline gate | Current gate |
| --- | --- | --- | --- |
| GET\|HEAD `/` | welcome closure | public | public |
| POST `api/auth/login` | `Auth\LoginController@store` | public | public |
| GET `login` | `Frontend\LoginController@show` | public (guest) | public (guest) |
| POST `api/auth/logout` | `Auth\LogoutController@destroy` | auth only | auth only |
| GET `api/auth/me` | `Auth\MeController@show` | auth only | auth only |
| GET `api/school` | `SchoolContextController@current` | auth + school, no permission | auth + school, no permission |
| GET `api/school/identifiers` | `SchoolContextController@identifiers` | auth + school, no permission | auth + school, no permission |
| GET `api/school-settings` | `SchoolSettingsController@show` | auth + school, no permission | auth + school, no permission |
| PUT\|PATCH `api/school-settings` | `SchoolSettingsController@update` | auth + school, no permission | auth + school, no permission |
| GET `api/school-settings/revisions` | `SchoolSettingsController@revisions` | auth + school, no permission | auth + school, no permission |
| GET `app` | `Livewire\Dashboard` | auth + school, no permission | auth + school, no permission |
| GET `app/school` | `Frontend\SchoolProfileController@index` | auth + school, no permission | auth + school, no permission |
| GET `app/modules/{module}` | `Frontend\ModuleNoticeController@show` | auth + school, no permission | auth + school, no permission |
| GET `app/roles` | `Livewire\RoleAssignments` | not present at audit baseline | auth + school, no permission (added by PR #64) |
| GET `app/school-settings` | `Livewire\SchoolSettings` | not present at audit baseline | auth + school, no permission (added by PR #64) |
| GET `api/academic-calendar/validation` | `AcademicCalendarController@validateCalendar` | auth + school, no permission | `academic_calendar.view` |
| GET `api/academic-years` | `AcademicCalendarController@years` | auth + school, no permission | `academic_calendar.view` |
| GET `api/academic-years/current` | `AcademicCalendarController@current` | auth + school, no permission | `academic_calendar.view` |
| GET `api/academic-years/{academicYear}/periods` | `AcademicCalendarController@periods` | auth + school, no permission | `academic_calendar.view` |
| POST `api/academic-years` | `AcademicCalendarController@storeYear` | auth + school, no permission | `academic_calendar.manage` |
| PUT\|PATCH `api/academic-years/{academicYear}` | `AcademicCalendarController@updateYear` | auth + school, no permission | `academic_calendar.manage` |
| POST `api/academic-years/{academicYear}/periods` | `AcademicCalendarController@storePeriod` | auth + school, no permission | `academic_calendar.manage` |
| PUT\|PATCH `api/academic-periods/{academicPeriod}` | `AcademicCalendarController@updatePeriod` | auth + school, no permission | `academic_calendar.manage` |
| GET `app/academic-calendar` | `Frontend\AcademicCalendarViewController@index` | auth + school, no permission | `academic_calendar.view` |
| GET `app/academic-calendar/manage` | `Livewire\CalendarAdministration` | auth + school, no permission | `academic_calendar.manage` |
| GET `api/academic-structure/levels` | `AcademicStructureController@levels` | `academic_structure.view` | unchanged |
| GET `api/academic-structure/levels/{educationalLevel}/grade-levels` | `AcademicStructureController@gradeLevels` | `academic_structure.view` | unchanged |
| GET `api/academic-structure/grade-levels` | `AcademicStructureController@allGradeLevels` | `academic_structure.view` | unchanged |
| GET `api/academic-structure/grade-levels/{gradeLevel}/classes` | `AcademicStructureController@classes` | `academic_structure.view` | unchanged |
| GET `api/academic-structure/classes/{classDefinition}/streams` | `AcademicStructureController@streams` | `academic_structure.view` | unchanged |
| GET `api/academic-structure/class-offerings` | `AcademicStructureController@offerings` | `academic_structure.view` | unchanged |
| POST `api/academic-structure/levels` | `AcademicStructureController@storeLevel` | `academic_structure.manage` | unchanged |
| PUT\|PATCH `api/academic-structure/levels/{educationalLevel}` | `AcademicStructureController@updateLevel` | `academic_structure.manage` | unchanged |
| POST `api/academic-structure/levels/{educationalLevel}/grade-levels` | `AcademicStructureController@storeGrade` | `academic_structure.manage` | unchanged |
| PUT\|PATCH `api/academic-structure/grade-levels/{gradeLevel}` | `AcademicStructureController@updateGrade` | `academic_structure.manage` | unchanged |
| POST `api/academic-structure/grade-levels/{gradeLevel}/classes` | `AcademicStructureController@storeClass` | `academic_structure.manage` | unchanged |
| PUT\|PATCH `api/academic-structure/classes/{classDefinition}` | `AcademicStructureController@updateClass` | `academic_structure.manage` | unchanged |
| POST `api/academic-structure/classes/{classDefinition}/streams` | `AcademicStructureController@storeStream` | `academic_structure.manage` | unchanged |
| PUT\|PATCH `api/academic-structure/streams/{stream}` | `AcademicStructureController@updateStream` | `academic_structure.manage` | unchanged |
| POST `api/academic-structure/class-offerings` | `AcademicStructureController@storeOffering` | `academic_structure.manage` | unchanged |
| PUT\|PATCH `api/academic-structure/class-offerings/{classOffering}` | `AcademicStructureController@updateOffering` | `academic_structure.manage` | unchanged |
| GET `api/role-assignments` | `RoleAssignmentController@index` | `roles.view` | unchanged |
| POST `api/role-assignments` | `RoleAssignmentController@store` | `roles.manage` | unchanged |
| DELETE `api/role-assignments/{roleAssignment}` | `RoleAssignmentController@destroy` | `roles.manage` | unchanged |

The application `/api/*` endpoints are registered through the `web` middleware group and use session authentication; no token guard or stateless API guard was found. This is existing architecture and was not changed.

## 4. Permission and role inventory

`config/authorization.php` defines 8 permissions and 4 roles.

| Permission | Configured roles | Enforced on routes after fix | Note |
| --- | --- | --- | --- |
| `school.view` | `school_member`, `school_editor`, `role_viewer`, `school_admin` (`*`) | none | Catalogued but never used as a route gate; see decision D-02. |
| `school_identifiers.view` | none directly; `school_admin` only via `*` | none | Catalogued but not assigned to any named role and not enforced; see D-02. |
| `academic_calendar.view` | `school_member`, `school_editor`, `school_admin` | 4 API reads + 1 frontend read | New in this audit. `role_viewer` is correctly denied. Also re-checked on Livewire read updates after role revocation (D-09). |
| `academic_calendar.manage` | `school_editor`, `school_admin` | 4 API writes + 1 frontend route | New in this audit; also re-checked inside Livewire writes. |
| `academic_structure.view` | `school_member`, `school_editor`, `school_admin` | 6 API reads | Already enforced at baseline. |
| `academic_structure.manage` | `school_editor`, `school_admin` | 10 API writes | Already enforced at baseline. |
| `roles.view` | `role_viewer`, `school_admin` | 1 API read | Already enforced at baseline. |
| `roles.manage` | `school_admin` only (`*`) | 2 API writes | Already enforced at baseline. |

Role notes:

- `school_editor` is labelled “School calendar editor” but grants both calendar and structure management. No production code assigns it; only tests and the CLI command can create assignments. See D-03.
- `school_admin` uses the wildcard `*` and therefore receives every current and future catalogue permission. This is characterized by a test rather than changed. See D-04.
- No permission exists for School Settings, so adding a gate would have invented a new product permission. See D-01.

## 5. Findings and fixes

### B04-F01 — Calendar routes had no permission gate

- **Attacker path:** any authenticated member in the selected school, including a `role_viewer` with no calendar permission, could call the eight calendar API endpoints and the two frontend calendar routes.
- **Boundary:** configured permission boundary between `school_member`/`role_viewer` and `school_editor`/`school_admin`.
- **Impact:** unauthorized calendar reads and writes; write endpoints create/update years and periods and emit audit events.
- **Evidence:** baseline `route:list` showed only `web`, `Authenticate` and `ResolveSchoolContext` on those routes; fixed JSON shows `EnsurePermission` on all ten.
- **Fix:** added `academic_calendar.view` to read routes and `academic_calendar.manage` to write routes in `routes/academic-calendar.php` and `routes/web.php`.
- **Regression tests:** `AcademicCalendarPermissionTest.php` covers anonymous denial, unauthorized denial with no mutation and no audit event, authorized editor/admin success, and read-permission enforcement.

### B04-F02 — Livewire writes did not replay the route permission

- **Attacker path:** a member who could reach a mounted `CalendarAdministration` component could invoke `saveYear`/`savePeriod` through `livewire/update` without replaying the page route gate.
- **Boundary:** frontend route gate versus component action authorization.
- **Impact:** same unauthorized calendar mutations as F01 through a different ingress.
- **Fix:** `CalendarAdministration::saveYear()` and `savePeriod()` now re-check `academic_calendar.manage` from the authenticated user, current school context and `SchoolPermissions`, returning a form error before any gateway call.
- **Regression tests:** direct `Livewire::test(...)->call('saveYear')` with a roleless member asserts no year was created; route test asserts `/app/academic-calendar/manage` is forbidden without manage permission.

### B04-F03 — Suspended/disabled accounts retained session authority

- **Attacker path:** an account suspended or disabled after login could keep using an existing session because `SchoolPermissions::has()` only checked role assignments and membership.
- **Boundary:** account lifecycle versus role-based authority.
- **Impact:** stale session could still pass `EnsurePermission` on role endpoints and calendar reads.
- **Fix:** `SchoolPermissions::has()` and `effectivePermissions()` now return empty authority when `$user->canAuthenticate()` is false. Login already treats only active accounts as authenticatable, so this aligns session authority with login policy instead of adding a new lifecycle rule.
- **Regression tests:** `RoleAssignmentSecurityTest.php` exercises suspended and disabled admins against role reads/writes and calendar reads, asserting denial and no assignment mutation.

### B04-F04 — Client `school_id` validation accepted ambiguous shapes

- **Attacker path:** clients could send `school_id: null`, `''` or other shapes to 20 request/controller/settings validation points. No cross-school mutation was demonstrated: domain actions still resolved the server-side school and foreign IDs were rejected. The gap was that ambiguous input was not rejected consistently before tenant resolution.
- **Boundary:** client-supplied tenant identifier versus server-resolved tenant context.
- **Impact:** inconsistent validation and weaker defence in depth; not an exploited tenant bypass at this baseline.
- **Fix:** changed all 20 rules from `['prohibited']` to `['missing']`. Presence of any `school_id` shape is now rejected; the context header/route remains the only tenant selector. Foreign identifiers still fail in the domain layer with 403/422 as appropriate.
- **Regression tests:** the offering and role-assignment suites assert the status matrix for `null`, `''`, `[]`, `'abc'`, own ID and foreign ID, plus unchanged rows after all attempts.

### B04-F05 — Missing security regression coverage

- **Finding:** no suite previously combined anonymous denial, role-restricted denial, no-mutation/no-audit assertions, suspended sessions, client `school_id` shapes and direct Livewire invocation.
- **Fix:** added `AcademicCalendarPermissionTest.php` and `RoleAssignmentSecurityTest.php`; expanded academic structure isolation and frontend calendar administration tests; updated fixtures to grant configured roles without weakening any assertion.
- **Evidence:** focused suite 209 passed / 942 assertions; full suite 262 passed / 1134 assertions.

## 6. Tenant-isolation evidence

| Ingress | Evidence | Result |
| --- | --- | --- |
| Structure write endpoints | `AcademicStructureIsolationTest` client `school_id` matrix, foreign class/year relationships, duplicate offerings | No mutation; foreign relationships rejected; duplicate offering rejected. |
| Calendar year/period endpoints | `AcademicCalendarIsolationTest`, `AcademicCalendarPermissionTest` | School resolved from context; cross-school year/period not addressable; denied writes create no audit event. |
| Role assignment endpoints | `RoleAssignmentSecurityTest` | Source injection ignored (`domain` stored); foreign `school_id` denied; viewer cannot assign/remove; target must be a member of the selected school in the domain action. |
| School settings | `SchoolSettingsIsolationTest` + `ValidateSchoolSettings` | Writes resolve through server context; client `school_id` now rejected as present input. |
| Frontend / Livewire | `FrontendFoundationTest`, `CalendarAdministrationTest` | Context membership is revalidated; remembered Livewire contexts are rejected after membership removal; direct component writes require manage permission. |
| Audit trail | `AuditTrailIsolationTest` | Events remain school scoped; denied calendar writes emit no event. |

Static review found no application-layer cross-school bypass in these flows. One database-level gap remains: `academic_periods` has `school_id` and an `academic_year_id` foreign key plus an index, but unlike `class_offerings` it does **not** have a composite `(school_id, academic_year_id)` foreign key. Application actions and validation currently preserve the relationship; the migration change is left as D-08 because it affects schema and rollback behaviour.

## 7. Risk register

| ID | Risk | Severity / confidence | Status |
| --- | --- | --- | --- |
| B04-F01 | Calendar permission boundary absent | High / high; deployment exposure depends on role assignments | Fixed, tested |
| B04-F02 | Livewire component action bypass | High / high | Fixed, tested |
| B04-F03 | Stale session after account disablement | Medium / high | Fixed, tested |
| B04-F04 | Ambiguous client tenant input accepted | Low / high; no exploit demonstrated | Hardened, tested |
| B04-F05 | Missing combined authorization regressions | Informational / high | Addressed |
| B04-O01 | School Settings write has no permission catalogue entry | Medium / high | Open decision D-01 |
| B04-O02 | `school.view` and `school_identifiers.view` catalogue entries unused | Low / high | Open decision D-02 |
| B04-O03 | `school_editor` label narrower than its grants | Low / high | Open decision D-03 |
| B04-O04 | Wildcard `school_admin` inherits future permissions | Medium / high | Characterized, open decision D-04 |
| B04-O05 | Role assignments are not written to the audit trail | Medium / high | Open decision D-05 |
| B04-O06 | CLI role bootstrap has no audit logging and does not enforce “first only” despite its docblock | Low / high | Open decision D-06 |
| B04-O07 | No default provisioning role for new members | Low / high | Open decision D-07 |
| B04-O08 | `academic_periods` lacks composite school/year FK | Low / high | Open decision D-08 |
| B04-O09 | Stale Livewire snapshot can keep returning calendar data after permission revocation | Low / high; read-only, no write bypass | Fixed: live read re-check, tested (D-09) |
| B04-O10 | Header-based school switching remains accepted per request | Low / high; cannot exceed memberships | Open decision D-10 |
| B04-O11 | Structure/settings domain actions rely on route gates plus tenancy guards, not independent `SchoolPermissions` checks | Low / high | Open decision D-11 |

## 8. Unresolved decisions

These are deliberately **not** implemented. They need a product, security or governance decision before production assumptions are made.

- **D-01 School Settings permission.** The catalogue has no settings permission. Adding `settings.view`/`settings.manage` would create a new product role contract. Current routes remain `auth + school` only.
- **D-02 `school.view` / `school_identifiers.view`.** Both permissions are catalogued but not enforced. Decide whether every authenticated school member may see the school context and identifiers, or whether these should become route gates. Note that no named role currently receives `school_identifiers.view`.
- **D-03 `school_editor` label and scope.** The role is labelled “School calendar editor” but also grants `academic_structure.manage`. Decide whether to relabel it or split calendar and structure editor roles. No production assignment source exists today, so this is not a live deployment claim.
- **D-04 Wildcard admin.** `school_admin` receives all future catalogue permissions automatically. A test characterizes this behaviour so a future change is visible, but the policy (keep bootstrap wildcard versus explicit enumeration) is unresolved.
- **D-05 Role-change auditing.** `AssignSchoolRole` and `RemoveSchoolRole` change authority but do not emit audit events. Decide whether role changes must be recorded and what privacy-safe fields are required.
- **D-06 CLI role bootstrap.** `roles:assign` is trusted CLI-only and writes `source = cli`, but it does not log to the audit trail and does not enforce the “first role assignment in a school” wording in its docblock. Decide whether to change the command, its documentation, or both.
- **D-07 Default provisioning role.** No code automatically assigns `school_member` or any other role. Decide the provisioning contract before deployments assume new members can view calendar/structure data.
- **D-08 Academic-period composite key.** Add a migration for a composite `(school_id, academic_year_id)` foreign key on `academic_periods`, with an explicit MySQL-safe rollback plan, if database-level tenancy is required in addition to application validation.
- **D-09 Livewire permission replay.** Only `ResolveSchoolContext` is persistent middleware, so `EnsurePermission` is not replayed on `livewire/update`. This was closed in this branch by re-checking `academic_calendar.view` in `render()`, `editYear()`, `editPeriod()` and `validateSelectedYear()`, and `academic_calendar.manage` in `saveYear()`/`savePeriod()`. Denied reads now return empty calendar data and a permission error instead of stale snapshot data; a regression test revokes the role assignment before a Livewire update. Adding `EnsurePermission` to persistent middleware remains a possible alternative for future components, but no stale-snapshot read path remains in `CalendarAdministration`.
- **D-10 Header-based school selection.** `ResolveSchoolContext` accepts `X-School-Id` as well as a server-side context and revalidates membership on every request. This cannot widen access beyond the user's memberships, but request validation does not reject the header. Decide whether per-request school switching is acceptable or whether context should be bound only to a server-issued session value.
- **D-11 Domain-level actor re-checks.** Academic structure and School Settings writes rely on route middleware (where a catalogue permission exists) plus tenancy/integrity guards in domain actions; they do not independently call `SchoolPermissions`. Decide whether defence-in-depth actor checks are required in those actions. This is distinct from D-01, where no settings permission exists at all.

## 9. Regression inventory

New files:

- `tests/Feature/Domains/AcademicCalendar/AcademicCalendarPermissionTest.php`
- `tests/Feature/Domains/Authorization/RoleAssignmentSecurityTest.php`

Extended files:

- `tests/Feature/Domains/AcademicStructure/AcademicStructureIsolationTest.php` — full client `school_id` shape matrix, foreign class/year relationships and duplicate offering cases.
- `tests/Feature/Frontend/CalendarAdministrationTest.php` — route denial, direct Livewire invocation denial and a role-revocation regression that fails closed on live calendar reads.
- `tests/Feature/Domains/AcademicCalendar/AcademicCalendarIsolationTest.php`, `tests/Feature/Domains/AuditTrail/AuditTrailIsolationTest.php`, `tests/Feature/Frontend/FrontendFoundationTest.php` — fixtures now grant configured roles so existing assertions remain meaningful under the new gates.

No existing assertion was removed, skipped or loosened.

## 10. Verification record

All commands below were run locally in the clean audit worktree. Exit codes are from the actual runs.

| Command | Exit | Result |
| --- | --- | --- |
| `make verify COMPOSE="docker compose -p zsmm-b04-recovered -f docker-compose.yml -f /home/praisetechzw/.local/share/opencode/worktree/b04-security-compose-override.yml"` | 0 | Pint PASS 209 files; PHPStan `[OK] No errors`; Composer audit clean; Pest 262 passed / 1134 assertions |
| `vendor/bin/pest <focused security paths> --colors=never` | 0 | 209 passed / 942 assertions |
| `php artisan route:list --json` (baseline and fixed) | 0 | 50 routes each; 19 → 29 `EnsurePermission` routes |
| `docker compose -p zsmm-b04-recovered ... config -q` | 0 | Compose configuration valid |
| `docker compose ... exec -T -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_PORT=3306 -e DB_DATABASE=school app php artisan migrate:fresh --force` | 0 | MySQL 8.0.46 migrations applied successfully |
| `git diff --check` | 0 | No whitespace errors |

Logs retained locally: `verify-final.log`, `mysql-migrate.log`, `b04-route-evidence/routes-baseline-6844b42.json` and `b04-route-evidence/routes-fixed.json`.

Database evidence label: the full Pest suite uses **in-memory SQLite** (`phpunit.xml` forces `DB_CONNECTION=sqlite`). MySQL 8.0.46 evidence is migration-only, from the isolated Compose stack. No MySQL-backed Pest run was performed.

Remote GitHub reads confirmed PR #60 and PR #61 are MERGED. No remote CI result is claimed for this audit branch; the PR is left for human review.

## 11. Independent challenge

A read-only security reviewer challenged the patch without editing files or running shell commands. Result: **no confirmed defects** — no ineffective or bypassable fix, no weakened test, no regression and no demonstrated high-risk authorization gap. The reviewer specifically verified middleware ordering, server-resolved school context in `EnsurePermission`, fail-closed lifecycle checks, nested calendar scoping, Livewire write re-checks and that `school_id => ['missing']` is stricter than `['prohibited']` for `null` input.

The reviewer raised three policy questions. D-09 (stale Livewire snapshot reads) was subsequently fixed with a live read permission re-check and regression test; D-10 (header-based school switching) and D-11 (domain-level actor re-checks) remain open decisions. Wildcard role semantics were independently raised and are already covered by D-04. A follow-up automated review also requested the current frontend School Settings route in the inventory; it is now listed in section 3.

## 12. Required report fields

```text
Task group: B-04 authorization, RBAC and tenant-isolation audit
Files changed: 24 application/route files, 7 test files, this report, docs/github/integration-tracker.md and docs/README.md
Commands run (with exit codes): see section 10
Result / evidence: 29/50 routes permission-gated; 262 tests / 1134 assertions; MySQL 8.0.46 migrations clean
Risks or limitations: decisions D-01 to D-11 remain open; SQLite is the test database; MySQL evidence is migration-only
Outstanding manual steps: human PR review and any product/governance decisions above
```
