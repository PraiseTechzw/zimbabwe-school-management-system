# B-04 authorization and tenant-isolation audit

**Task:** B-04 backend authorization / tenant-isolation audit and narrow remediation  
**Audited baseline:** `c21d0a90530cc2764853e6b3eabb277501da696b` (`local main` and `origin/main` at audit start)  
**Current `origin/main` observed before reporting:** `6844b4289af6c5446b80d897f73de39d4bb6aa7b` (PR #61 final Academic Structure evidence; no backend code changed)
**Working branch:** `audit/b04-authorization-tenant-isolation`  
**Evidence class:** executed local application tests and command output; static route/source review. Remote GitHub Actions results are **not** claimed in this report.  
**Handoff:** Audit Coverage agent / human reviewer.

## 1. Scope and method

This audit inventories every application-owned route and checks authentication, permission gates, server-resolved school context, ownership checks, role assignment controls, tenant isolation, and sensitive-data exposure. It reviews `routes/*.php`, middleware, `config/authorization.php`, controllers, FormRequests, Livewire components, frontend gateways, domain guards, and the linked Pest suites.

Evidence sources:

- `php artisan route:list --except-vendor --json` captured before and after the fix.
- Source review of middleware, controllers, FormRequests, domain actions, Livewire component and gateway.
- Executed focused Pest security suites and the full `make verify` gate in the isolated Compose project `b04-audit`.
- A `gstack-cso` run (`1795806574566-c85a630b4c8088a1`) reported `completeness: partial` from a static snapshot; it produced no findings and is **not** presented as an executed security test.

Scope limits:

- Sprint 0 / Phase 1 backend foundation only; no new school workflow was added.
- Frontend visibility is treated as presentation, not as an authorization boundary.
- No RBAC rewrite, second permission system, or unrelated domain change was made.
- MySQL was not available in the audit environment (`command -v mysql` exit 1), so MySQL-specific integration was not run. Tests used the project's SQLite test database.
- No remote CI, review approval, deployment, or merge result is asserted without command output.

## 2. Route inventory and authorization coverage matrix

Authoritative counts after remediation:

- **42 application-owned route registrations** from `php artisan route:list --except-vendor --json`.
- **73 expanded method–path bindings** (`GET|HEAD` and `PUT|PATCH` expanded by Laravel).
- **50 declared HTTP method choices** after excluding implicit `HEAD` and counting `PUT`/`PATCH` as declared choices.
- **37** app routes are behind `auth` + `school.context`; **2** are auth-only (`api/auth/logout`, `api/auth/me`); **3** are intentionally public (`/`, `/login`, `api/auth/login`).
- **24** app routes now have an explicit permission gate when the frontend management route is included: 16 Academic Structure routes, 3 role-assignment routes, 4 calendar write API routes, and `/app/academic-calendar/manage`.
- **13** app routes remain membership-only with unresolved permission decisions (calendar reads, school context/identifiers, school settings, and read-only frontend screens). These are documented as unresolved findings, not silently accepted.

Middleware abbreviations: `W`=`web`, `A`=`auth`, `S`=`school.context` / `ResolveSchoolContext`, `P:x`=`EnsurePermission:x`. All rows below were checked against the post-rebase `route:list` output; the full machine-readable inventory is `/home/praisetechzw/b04-audit-evidence/route-inventory.tsv`.

| # | Method | Path | Controller / action | Module | Auth / permission | Tenant and ownership | Tests linked | Risk; required check / audit result |
|---:|---|---|---|---|---|---|---|---|
| 1 | GET/HEAD | `/` | Closure → welcome view | Foundation | `W` | No school data | `FoundationTest` | Public intended; guest 200 verified. |
| 2 | GET/HEAD | `api/academic-calendar/validation` | `AcademicCalendarController@validateCalendar` | Calendar | `W,A,S`; no `academic_calendar.view` gate | `school_id` rejected; year scoped to current school | `AcademicCalendarIsolationTest` | Guest 401 and foreign-year 404 covered. Catalogued view permission is not enforced — unresolved F04. |
| 3 | PUT/PATCH | `api/academic-periods/{academicPeriod}` | `AcademicCalendarController@updatePeriod` | Calendar | `W,A,S,P:academic_calendar.manage` (fixed) | `scopedPeriod()`; domain integrity guard | `AcademicCalendarIsolationTest`, new `AcademicCalendarPermissionTest` | Fixed F01. Member/no-role/role_viewer 403, editor/admin 200, foreign 404, client `school_id` 422 covered. |
| 4 | GET/HEAD | `api/academic-structure/class-offerings` | `AcademicStructureController@offerings` | Structure | `W,A,S,P:academic_structure.view` | `forCurrentSchool()`; validated year filter | `AcademicStructureIsolationTest`, `AcademicStructurePermissionTest` | Guest 401, viewer/member 403, cross-school no leak covered. |
| 5 | POST | `api/academic-structure/class-offerings` | `AcademicStructureController@storeOffering` | Structure | `W,A,S,P:academic_structure.manage` | `assertCurrent()`; `school_id` prohibited; parent scoping | `AcademicStructureIsolationTest`, `AcademicStructurePermissionTest` | New regression: create with `school_id` 422; foreign class/year generic 422; member 403. |
| 6 | PUT/PATCH | `api/academic-structure/class-offerings/{classOffering}` | `AcademicStructureController@updateOffering` | Structure | `W,A,S,P:academic_structure.manage` | `scopedOffering()`; domain integrity guard | `AcademicStructureIsolationTest`, `AcademicStructurePermissionTest` | New regression: update `school_id` 422, foreign school 403, foreign offering 404 without data leak; member 403. |
| 7 | PUT/PATCH | `api/academic-structure/classes/{classDefinition}` | `AcademicStructureController@updateClass` | Structure | `W,A,S,P:academic_structure.manage` | `scopedClass()`; parent guard | `AcademicStructureIsolationTest` | Guest/foreign/member checks covered. |
| 8 | GET/HEAD | `api/academic-structure/classes/{classDefinition}/streams` | `AcademicStructureController@streams` | Structure | `W,A,S,P:academic_structure.view` | `scopedClass()` + `forCurrentSchool()` | `AcademicStructureIsolationTest` | Foreign parent 404; no cross-school stream leak. |
| 9 | POST | `api/academic-structure/classes/{classDefinition}/streams` | `AcademicStructureController@storeStream` | Structure | `W,A,S,P:academic_structure.manage` | `scopedClass()`; parent guard | New permission coverage in `AcademicStructurePermissionTest` | Member 403; editor 201; foreign parent generic 422. |
| 10 | GET/HEAD | `api/academic-structure/grade-levels` | `AcademicStructureController@allGradeLevels` | Structure | `W,A,S,P:academic_structure.view` | `forCurrentSchool()` | `AcademicStructureIsolationTest` | Cross-school no leak; viewer 403. |
| 11 | PUT/PATCH | `api/academic-structure/grade-levels/{gradeLevel}` | `AcademicStructureController@updateGrade` | Structure | `W,A,S,P:academic_structure.manage` | `scopedGrade()`; parent guard | `AcademicStructureIsolationTest` | Foreign 404; member 403. |
| 12 | GET/HEAD | `api/academic-structure/grade-levels/{gradeLevel}/classes` | `AcademicStructureController@classes` | Structure | `W,A,S,P:academic_structure.view` | `scopedGrade()` + `forCurrentSchool()` | `AcademicStructureIsolationTest` | Foreign parent 404; viewer 403. |
| 13 | POST | `api/academic-structure/grade-levels/{gradeLevel}/classes` | `AcademicStructureController@storeClass` | Structure | `W,A,S,P:academic_structure.manage` | `scopedGrade()`; parent guard | New permission coverage in `AcademicStructurePermissionTest` | Member 403; editor 201; foreign parent generic 422. |
| 14 | GET/HEAD | `api/academic-structure/levels` | `AcademicStructureController@levels` | Structure | `W,A,S,P:academic_structure.view` | `forCurrentSchool()` | `AcademicStructureIsolationTest`, `AcademicStructurePermissionTest` | Guest 401; viewer/member 403; cross-school no leak. |
| 15 | POST | `api/academic-structure/levels` | `AcademicStructureController@storeLevel` | Structure | `W,A,S,P:academic_structure.manage` | `assertCurrent()`; `school_id` prohibited | `AcademicStructureIsolationTest`, `AcademicStructurePermissionTest` | Client tenant field rejected; member 403. |
| 16 | PUT/PATCH | `api/academic-structure/levels/{educationalLevel}` | `AcademicStructureController@updateLevel` | Structure | `W,A,S,P:academic_structure.manage` | `scopedLevel()` | `AcademicStructureIsolationTest` | Foreign 404; member 403. |
| 17 | GET/HEAD | `api/academic-structure/levels/{educationalLevel}/grade-levels` | `AcademicStructureController@gradeLevels` | Structure | `W,A,S,P:academic_structure.view` | `scopedLevel()` + `forCurrentSchool()` | `AcademicStructureIsolationTest` | Foreign parent 404; viewer 403. |
| 18 | POST | `api/academic-structure/levels/{educationalLevel}/grade-levels` | `AcademicStructureController@storeGrade` | Structure | `W,A,S,P:academic_structure.manage` | `scopedLevel()`; parent guard | New permission coverage in `AcademicStructurePermissionTest` | Member 403; editor 201; foreign parent generic 422. |
| 19 | PUT/PATCH | `api/academic-structure/streams/{stream}` | `AcademicStructureController@updateStream` | Structure | `W,A,S,P:academic_structure.manage` | `scopedStream()` | `AcademicStructureIsolationTest` | Foreign 404; member 403. |
| 20 | GET/HEAD | `api/academic-years` | `AcademicCalendarController@years` | Calendar | `W,A,S`; no `academic_calendar.view` gate | `school_id` rejected; `forCurrentSchool()` | `AcademicCalendarIsolationTest` | Guest 401 and cross-school no leak covered. View-permission gate unresolved F04. |
| 21 | POST | `api/academic-years` | `AcademicCalendarController@storeYear` | Calendar | `W,A,S,P:academic_calendar.manage` (fixed) | `assertCurrent()`; `school_id` prohibited; school lock | `AcademicCalendarIsolationTest`, new permission test | Fixed F01. Member 403, editor/admin 201, client `school_id` 422, null `school_id` ignored. |
| 22 | GET/HEAD | `api/academic-years/current` | `AcademicCalendarController@current` | Calendar | `W,A,S`; no view gate | `school_id` rejected; current year scoped | `AcademicCalendarIsolationTest` | Guest 401, foreign selection 403, null-current covered. View gate unresolved F04. |
| 23 | PUT/PATCH | `api/academic-years/{academicYear}` | `AcademicCalendarController@updateYear` | Calendar | `W,A,S,P:academic_calendar.manage` (fixed) | `scopedYear()`; `assertCurrent()`; integrity guard | `AcademicCalendarIsolationTest`, new permission test | Fixed F01. Member 403, editor/admin 200, foreign 404, `school_id` 422 covered. |
| 24 | GET/HEAD | `api/academic-years/{academicYear}/periods` | `AcademicCalendarController@periods` | Calendar | `W,A,S`; no view gate | `scopedYear()` + `forCurrentSchool()` | `AcademicCalendarIsolationTest` | Guest 401; foreign year 404. View gate unresolved F04. |
| 25 | POST | `api/academic-years/{academicYear}/periods` | `AcademicCalendarController@storePeriod` | Calendar | `W,A,S,P:academic_calendar.manage` (fixed) | Route year authoritative; `scopedYear()`; `school_id` rejected | `AcademicCalendarIsolationTest`, new permission test | Fixed F01. Member 403, editor/admin 201, foreign year no leak. |
| 26 | POST | `api/auth/login` | `Auth\LoginController@store` | Auth | `W`, throttled; intentionally public | No school context by design; client `school_id` cannot grant membership | `AuthenticationTest` | Public intended; throttling, generic failure and tenant-input no-widening covered. |
| 27 | POST | `api/auth/logout` | `Auth\LogoutController@destroy` | Auth | `W,A` | No school context | `AuthenticationTest` | Guest 401 covered. |
| 28 | GET/HEAD | `api/auth/me` | `Auth\MeController@show` | Auth | `W,A` | Allow-listed fields | `AuthenticationTest` | Guest 401; no school/remember-token leak covered. |
| 29 | GET/HEAD | `api/role-assignments` | `RoleAssignmentController@index` | Authorization | `W,A,S,P:roles.view` | `assertCurrent()`; client `school_id` rejected; domain double-check | `RolesPermissionsTest` | Guest/no-role 403, viewer 200, cross-school no leak. |
| 30 | POST | `api/role-assignments` | `RoleAssignmentController@store` | Authorization | `W,A,S,P:roles.manage` | `assertCurrent()`; `school_id` prohibited; same-school actor required; no escalation | `RolesPermissionsTest` | Cross-school user 422, non-escalation, editor 403 covered. |
| 31 | DELETE | `api/role-assignments/{roleAssignment}` | `RoleAssignmentController@destroy` | Authorization | `W,A,S,P:roles.manage` | Client `school_id` rejected; school-scoped removal | `RolesPermissionsTest` | Viewer/editor 403; cross-school removal denied. |
| 32 | GET/HEAD | `api/school` | `SchoolContextController@current` | School context | `W,A,S`; no `school.view` gate | `assertCurrent()`; tenant-aware headers | `SchoolIsolationTest` | Guest/no-membership/foreign selection covered; catalogued view gate unresolved F04. |
| 33 | GET/HEAD | `api/school-settings` | `SchoolSettingsController@show` | School settings | `W,A,S`; no permission exists | `school_id` rejected; scoped resolve | `SchoolSettingsIsolationTest` | Tenant isolation covered; any member can read — unresolved F03. |
| 34 | PUT/PATCH | `api/school-settings` | `SchoolSettingsController@update` | School settings | `W,A,S`; no permission exists | `assertCurrent()`; `school_id` and secret fields prohibited | `SchoolSettingsIsolationTest` | Tenant isolation covered; any member can write — unresolved F03. |
| 35 | GET/HEAD | `api/school-settings/revisions` | `SchoolSettingsController@revisions` | School settings | `W,A,S`; no permission exists | `school_id` rejected; scoped revisions | `SchoolSettingsIsolationTest` | Tenant isolation covered; any member can read history — unresolved F03. |
| 36 | GET/HEAD | `api/school/identifiers` | `SchoolContextController@identifiers` | School context | `W,A,S`; no `school_identifiers.view` gate | `assertCurrent()`; `forCurrentSchool()` | `SchoolIsolationTest` | Foreign selection 403 and scoped identifiers covered; gate unresolved F04. |
| 37 | GET/HEAD | `app` | `App\Livewire\Dashboard` | Frontend | `W,A,S` | Data provider uses server context | `FrontendFoundationTest` | Guest redirect, no-membership 403, cross-school no leak covered. |
| 38 | GET/HEAD | `app/academic-calendar` | `Frontend\AcademicCalendarViewController@index` | Frontend | `W,A,S`; no view gate | Years/periods scoped to server context | `FrontendFoundationTest`, `CalendarAdministrationTest` | Guest/cross-school covered. Manage link remains visible to unauthorized members (F05) but route is now gated. |
| 39 | GET/HEAD | `app/academic-calendar/manage` | `App\Livewire\CalendarAdministration` | Frontend | `W,A,S,P:academic_calendar.manage` (fixed) | Persistent school context; gateway scopes records | `CalendarAdministrationTest` | Fixed F01. Member 403, editor 200, unowned school selection 403, no foreign name in response. |
| 40 | GET/HEAD | `app/modules/{module}` | `Frontend\ModuleNoticeController@show` | Frontend | `W,A,S` | No school data rendered | `FrontendFoundationTest` | Guest redirect/no-membership covered; no tenant test required. |
| 41 | GET/HEAD | `app/school` | `Frontend\SchoolProfileController@index` | Frontend | `W,A,S`; no view gates | Identifiers scoped via data provider | `FrontendFoundationTest` | Cross-school no leak covered; `school.view` / identifier gate unresolved F04. |
| 42 | GET/HEAD | `login` | `Frontend\LoginController@show` | Frontend | `W,G` | No school context | `FrontendFoundationTest` | Public guest-only intended; authenticated redirect covered. |

### Framework/vendor runtime routes (outside application route files)

`php artisan route:list --json` showed 50 registrations in total; the 8 additional entries are framework/package routes: Livewire JavaScript assets, `livewire/update`, `livewire/upload-file`, `livewire/preview-file/{filename}`, signed `storage/{path}` GET/PUT, and health `/up`.

- `livewire/update` has only `web` at route level. This application persists `auth` and `ResolveSchoolContext` for Livewire; `EnsurePermission` is not persistent. The calendar gateway now performs its own `academic_calendar.manage` check on every write, so component actions cannot bypass the screen gate.
- File upload/preview and storage routes are signature-protected and unauthenticated. No application usage of tenant file storage was found. Tenant-bound signing/path rules are a residual hardening item (F07) if file storage is introduced.
- Health and asset routes are intentionally public.

## 3. Permission and role matrix

### Configured permissions and effective enforcement

| Permission | Configured roles | Enforcement after B-04 | Status |
|---|---|---|---|
| `school.view` | `school_member`, `school_editor`, `role_viewer`, `school_admin` (`*`) | None; school context is membership-only | Unresolved F04 |
| `school_identifiers.view` | `school_admin` (`*`) only; no non-admin role grants it | None | Unresolved F04 |
| `academic_calendar.view` | `school_member`, `school_editor`, `school_admin` (`*`) | None on 4 calendar read endpoints or the read-only calendar screen | Unresolved F04 |
| `academic_calendar.manage` | `school_editor`, `school_admin` (`*`) | 4 calendar API write routes, `/app/academic-calendar/manage`, and `DomainCalendarManagementGateway` writes | Fixed F01 |
| `academic_structure.view` | `school_member`, `school_editor`, `school_admin` (`*`) | 6 structure read routes | Existing, test coverage extended |
| `academic_structure.manage` | `school_editor`, `school_admin` (`*`) | 10 structure write routes | Existing, test coverage extended |
| `roles.view` | `role_viewer`, `school_admin` (`*`) | `GET api/role-assignments` | Existing |
| `roles.manage` | `school_admin` (`*`) | Role create/delete plus domain non-escalation checks | Existing |

`school_admin` uses the configured wildcard `*`; `SchoolPermissions` resolves only permissions present in the catalogue and requires an active assignment in the current school. Unknown roles/permissions fail closed. There is no direct user-permission table.

### Role label correction

`school_editor` was labelled **School calendar editor** while its configured permissions include `academic_structure.manage`. The label is now **School editor**. The role slug, permission list, assignment records, and route behavior are unchanged. No data migration is required; only display text changes.

## 4. Tenant-isolation evidence

No confirmed cross-school IDOR or tenant-escalation defect was found in the reviewed HTTP, Livewire, frontend-read, role, settings, calendar, or structure paths. Evidence:

- `ResolveSchoolContext` derives all authorisation from authenticated memberships. `X-School-Id` and `school_id` can only select among those memberships; unauthorised, malformed, conflicting, and empty identifiers fail closed (400/403).
- Multi-school users must select a school. Livewire remembers the selection in session and revalidates it against current memberships on every update.
- `SchoolContext` is cleared after normal requests; Livewire defers clearing until request termination so persistent middleware can revalidate.
- School-owned models use explicit `forCurrentSchool()` / `forSchool()` scopes and `assertCurrent()`; foreign route IDs return 404 or generic 422 and do not reveal foreign names/metadata.
- `prohibited` FormRequest rules reject non-empty client `school_id`; null/empty values are ignored and cannot change server-resolved ownership.
- Role assignment is school-scoped and non-escalating: an actor cannot grant a role containing permissions they do not already hold in that school, and a role assigned in another school grants nothing.
- Tenant-aware `Cache-Control: no-store` and `Vary` headers are asserted on JSON calendar responses; `api/school` has its own tenant-header test.

Executed tenant regressions include: cross-school calendar year/period 404, foreign offering update 404 with no leak, foreign class/year offering 422, unowned `X-School-Id` on the calendar management screen 403 without the foreign school name, role in another school grants no calendar permission, empty school IDs do not widen access, and roleless/member/role_viewer calendar writes 403.

## 5. Security findings

### F01 — High — fixed: calendar writes had no permission gate

Baseline route middleware on the 4 calendar write API routes was only `web,auth,school.context`. `academic_calendar.manage` existed in the catalogue but was never enforced. A roleless school member, `school_member`, or `role_viewer` could create/update academic years and periods and could use the frontend management screen and gateway.

**Fix:** reuse the existing architecture only — add `EnsurePermission:academic_calendar.manage` to the 4 write routes and the management screen, and add an internal `SchoolPermissions::has(...)` assertion in `DomainCalendarManagementGateway` for all gateway writes. No new permission, policy, gate, or domain rewrite was introduced.

**Regression evidence:** `AcademicCalendarPermissionTest` denies all 4 write routes for no role / `school_member` / `role_viewer`, allows `school_editor` / `school_admin`, and denies a role assigned only in another school. `CalendarAdministrationTest` denies the management screen, a Livewire write, and a gateway write.

### F02 — Medium — fixed: misleading `school_editor` label

The role label claimed calendar-only scope while the role also grants Academic Structure management. Changed to `School editor`; no permission or assignment change.

### F03 — Medium — unresolved: school settings remain membership-only

There is no settings permission in `config/authorization.php`, and all 3 settings routes rely on school membership alone. Tenant isolation is sound, but any school member can read settings/revisions and update settings.

**Why not fixed here:** adding a new permission and granting it to roles is a breaking access change requiring a product decision and an assignment migration plan. This audit did not introduce a second permission system or change role contents without documented impact. Required next step: define `school_settings.view` / `school_settings.manage`, assign them deliberately, add negative/positive tests, and migrate existing role occupants.

### F04 — Medium — unresolved: catalogued read permissions are not enforcement boundaries

`school.view`, `school_identifiers.view`, and `academic_calendar.view` are configured but not applied to the relevant school-context, identifier, calendar-read, and read-only frontend routes. `school_identifiers.view` is not granted to any configured non-admin role.

**Why not fixed here:** gating these reads changes access for roleless members and current role occupants, and identifier visibility needs an explicit ownership decision. Frontend navigation changes are presentation-only and not a security boundary. Required next step: decide role grants, document impact, then apply existing `EnsurePermission` middleware with tests.

### F05 — Low — unresolved: manage link shown to unauthorized members

The read-only calendar Blade view still renders the management link for any member. The target route and gateway now return 403, so this is a UX/informational issue, not an authorization bypass. Hide the link by permission in a separate UI change if desired.

### F06 — Low — mitigated: Livewire update route has no permission middleware

`livewire/update` is framework-owned and only has `web` route middleware. Auth and school context are replayed by Livewire, but `EnsurePermission` is not persistent. Calendar gateway writes now self-enforce `academic_calendar.manage`, closing the current bypass. Future Livewire actions must perform their own domain/gateway authorization or register a persistent permission mechanism.

### F07 — Low — unresolved hardening: signed framework file routes are not tenant-bound

Livewire upload/preview and Laravel signed storage routes are unauthenticated by design and rely on signatures. No application tenant-file usage was found. If tenant files are introduced, signatures must include school scope and storage paths must be tenant-separated.

### F08 — Informational — no cross-school IDOR confirmed

Tested offering-by-ID update, foreign offering class/year references, foreign calendar IDs, foreign school selection, cross-school role assignment, and frontend unowned selection. All failed closed without foreign data in responses. The previously untested offering `school_id` prohibition is now explicitly covered for create and update.

### F09 — Informational — architecture boundary preserved

No Gate/Policy definitions exist; authorization remains the existing middleware + `SchoolPermissions` + domain-guard architecture. No RBAC rewrite, duplicate permission system, or unrelated school workflow was added.

## 6. Tests added or changed

B-04 adds **20 tests / 98 assertions** relative to the pre-B-04 tree. The final rebased branch reports **264 passed / 1105 assertions**; the original audited `main` reported 243 / 990 and current `main` reported 244 / 1007 before B-04 changes.

| File | Added regression coverage |
|---|---|
| `tests/Feature/Domains/AcademicCalendar/AcademicCalendarPermissionTest.php` (new) | Calendar writes denied for no role / `school_member` / `role_viewer`; allowed for `school_editor` / `school_admin`; cross-school role grants nothing. |
| `tests/Feature/Domains/AcademicCalendar/AcademicCalendarIsolationTest.php` | Calendar year/period updates reject client `school_id`; null `school_id` cannot widen ownership; fixtures now carry a calendar-capable role. |
| `tests/Feature/Domains/AcademicStructure/AcademicStructureIsolationTest.php` | Offering create/update `school_id` rejection; foreign school context on update 403; foreign offering update 404 without leak; foreign class/year IDs generic 422; null `school_id` ignored. |
| `tests/Feature/Domains/AcademicStructure/AcademicStructurePermissionTest.php` | Nested grade/class/stream writes and offering create/update denied for `school_member`, allowed for `school_editor`. |
| `tests/Feature/Domains/School/SchoolIsolationTest.php` | Empty header/query school IDs fail closed without widening access. |
| `tests/Feature/Frontend/CalendarAdministrationTest.php` | Management screen 403 for non-editor; Livewire write denial; gateway permission enforcement; unowned school selection on management route 403 with no foreign school name. |
| `tests/Feature/Domains/AuditTrail/AuditTrailIsolationTest.php` | Existing audit fixtures now use `school_editor` because calendar writes require `academic_calendar.manage`. |

## 7. Verification evidence

Commands were executed locally in the isolated worktree. Pre-restart runs used `/tmp/opencode/b04-authorization-audit`; final post-restart verification used `/home/praisetechzw/.local/share/opencode/worktree/b04-authorization-audit-recovered`. Exit codes are from the final post-fix state unless marked baseline.

| Command | Result | Evidence |
|---|---|---|
| `make COMPOSE="docker compose -p b04-audit" verify` at baseline `c21d0a9` | Exit 0; Pint 207 files; PHPStan `[OK] No errors`; Composer audit clean; **243 passed / 990 assertions** | `/tmp/opencode/b04-baseline-verify.log` |
| Focused security suites before rebase (`docker compose -p b04-audit exec -T app php artisan test …`) | Exit 0; **163 passed / 654 assertions** | Original log was removed by the environment restart; result is retained in this audit session. |
| `make COMPOSE="docker compose -p b04-audit" verify` before rebase | Exit 0; Pint 208 files; PHPStan `[OK] No errors`; Composer audit clean; **263 passed / 1088 assertions** | Original log was removed by the environment restart; result is retained in this audit session. |
| Focused security suites after rebase, inside `b04-audit-r` | Exit 0; **164 passed / 671 assertions** | `/home/praisetechzw/b04-audit-evidence/focused-tests.log` |
| `make COMPOSE="docker compose -p b04-audit-r -f docker-compose.yml -f /home/praisetechzw/b04-audit-evidence/compose-override.yml" verify` after rebase | Exit 0; Pint 208 files; PHPStan `[OK] No errors`; Composer audit `No security vulnerability advisories found`; **264 passed / 1105 assertions** | `/home/praisetechzw/b04-audit-evidence/make-verify.log` |
| `docker compose -p b04-audit-r … exec -T app php artisan route:list --except-vendor --json` after rebase | 42 app routes; 73 method tokens; 50 declared method choices | `/home/praisetechzw/b04-audit-evidence/route-list.json`, `/home/praisetechzw/b04-audit-evidence/route-inventory.tsv` |
| `docker compose -p b04-audit config -q` | Exit 0 | Command output captured in this audit session |
| `git diff --check` | Exit 0 | Command output captured in this audit session |
| `git ls-remote origin refs/heads/main` | Initially `c21d0a9…`; final observation `6844b4289af6c5446b80d897f73de39d4bb6aa7b` | Command output captured in this audit session |
| `command -v mysql` | Not available; MySQL integration not run | Command output captured in this audit session |
| `gstack-cso` static run `1795806574566-c85a630b4c8088a1` | `completeness: partial`; no findings; static snapshot only, not an executed security test | Tool run record |

No GitHub Actions check-run result, review approval, deployment, or merge is claimed for B-04 in this document.

## 8. Unresolved findings and risk register

| ID | Risk | Likelihood / impact | Status | Required owner action |
|---|---|---|---|---|
| F03 | Any school member can read/update school settings | Medium / high | Open | Define settings permissions, assign roles, migration plan, tests. |
| F04 | Catalogued read permissions are not enforced; identifier visibility undecided | Medium / medium | Open | Decide role grants and impact, then gate reads with existing middleware. |
| F05 | Manage link visible to users who receive 403 | High / low | Open | Conditional Blade link; no security blocker. |
| F06 | Future Livewire actions could bypass route permission gates | Medium / high | Mitigated for calendar; open pattern risk | Require gateway/domain checks for every new Livewire write. |
| F07 | Future tenant files could be exposed via signed routes | Low / high if introduced | Open hardening | Tenant-bound signatures and paths before enabling file storage. |
| F01 | Roleless calendar writes | High / high | **Fixed and regression-tested** | Human review of role impact; no migration required. |
| F02 | Misleading role label | Medium / low | **Fixed** | Confirm display text in UI copy if referenced. |

## 9. Integration tracker and traceability

- `docs/github/integration-tracker.md` was stale at `28079f5…`; it is reconciled to the observed current `origin/main` `6844b4289af6c5446b80d897f73de39d4bb6aa7b`, including PRs #59–#61.
- Audited baseline: `c21d0a90530cc2764853e6b3eabb277501da696b`.
- Current remote baseline at report time: `6844b4289af6c5446b80d897f73de39d4bb6aa7b`.
- Change branch: `audit/b04-authorization-tenant-isolation` (rebased onto `6844b42`).
- Review PR: [#62](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/62) — opened, not merged; no remote check result is claimed here.
- Route evidence: `/home/praisetechzw/b04-audit-evidence/route-list.json` and `/home/praisetechzw/b04-audit-evidence/route-inventory.tsv`.
- Verification evidence: `/home/praisetechzw/b04-audit-evidence/make-verify.log`, `/home/praisetechzw/b04-audit-evidence/focused-tests.log`, `/home/praisetechzw/b04-audit-evidence/compose-override.yml`, and `/home/praisetechzw/b04-audit-evidence/final-verify.log` (host-equivalent rerun). Earlier `/tmp/opencode/b04-*` artifacts were removed by an environment restart; their results are preserved in this report.
- This report is the authoritative B-04 role/permission matrix and risk register.

### Handoff to Audit Coverage agent

Please verify that the 42-route matrix, the fixed calendar write gates, the unresolved settings/read-permission decisions, and the risk register match your coverage record. Do not mark F03/F04/F05/F06/F07 closed without a separate decision, implementation, and tests. Do not report B-04 CI or review status until remote command output exists.
