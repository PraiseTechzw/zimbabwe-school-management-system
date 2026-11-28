# B-04 authorization and tenant-isolation audit

**Task:** B-04 backend authorization / tenant-isolation audit and narrow follow-up remediation
**Audited baseline:** `c21d0a90530cc2764853e6b3eabb277501da696b` (`origin/main` at audit start)
**Reconciled baseline:** `81cba1d84752b69d35a3ef91d407382c116355f5` (`origin/main` after PRs #62–#68; checked with `git ls-remote` before this report)
**Working branch:** `audit/b04-f03-f07-followup`
**Review PR:** [#72](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/72) — **OPEN, unmerged**; `gh pr view 72` reported `mergedAt: null`.
**Evidence class:** executed local application tests and command output; static route/source review. Remote GitHub Actions results, review approval, deployment, and merge of PR #72 are **not** claimed.

## 1. Scope and method

This audit inventories every application-owned route and checks authentication, permission gates, server-resolved school context, ownership checks, role assignment controls, tenant isolation, sensitive-data exposure, private file storage, and Livewire persistence. It reviews `routes/*.php`, middleware, `config/authorization.php`, controllers, FormRequests, Livewire components, frontend gateways, domain guards, and the linked Pest suites.

Evidence sources:

- `php artisan route:list --except-vendor --json` and `php artisan route:list --json`, captured after the final reconciliation.
- Source review of middleware, controllers, FormRequests, domain actions, Livewire components/gateways, and private-disk configuration.
- Executed focused Pest security suites and the full `make verify` gate in the isolated Compose project `b04-audit-r`.
- GitHub state reads with `git ls-remote`, `git fetch --prune`, `gh pr list`, and `gh pr view`; no remote CI status is asserted for PR #72.
- A `gstack-cso` run (`1795806574566-c85a630b4c8088a1`) reported `completeness: partial` from a static snapshot; it produced no findings and is **not** presented as an executed security test.

Scope limits:

- Sprint 0 / Phase 1 backend foundation only; no new school workflow was added.
- Frontend visibility is presentation, not an authorization boundary.
- No RBAC rewrite, second permission system, Gate/Policy layer, unrelated domain change, or undocumented breaking role change was made.
- MySQL was not available in the audit environment (`command -v mysql` exit 1), so MySQL-specific integration was not run. Tests used the project's SQLite test database.
- PR #62 is MERGED at `98e8226459541a56b02df25ed640b1996f76009d` and PR #63 is MERGED at `6f2d86840aeb7b3cc605f7676c729408ce4a1b34`. Their changes were retained. F03–F07 were replayed onto the merged baseline without force-push or shared-history rewrite.
- PR #64 added `/app/school-settings` and `/app/roles` as membership-only routes; those are covered as F10 in this follow-up.
- No remote CI, review approval, deployment, or merge result is asserted without fresh command output.

## 2. Route inventory and authorization coverage matrix

Authoritative counts after final reconciliation:

- **46 application-owned route registrations** from `php artisan route:list --except-vendor --json` (80 expanded method tokens; 46 declared method choices after treating `GET|HEAD` and `PUT|PATCH` as one declared choice each).
- **43** app routes are behind `auth`; **41** of those also resolve school context.
- **38** app routes have an explicit `EnsurePermission` gate: 16 Academic Structure, 10 calendar, 3 role-assignment, 3 school-settings API, 3 `school.view`, 2 `roles.view`, 1 `school_identifiers.view`, 1 `school_settings.manage`, and 1 `school_settings.view` frontend route.
- **2** app routes use the tenant-signed storage guard instead of a permission gate.
- **1** app route is membership-only and renders no school data (`app/modules/{module}`); **2** are auth-only (`api/auth/logout`, `api/auth/me`); **3** are intentionally public (`/`, `/login`, `api/auth/login`). No school-data route remains membership-only.

Middleware abbreviations: `W`=`web`, `A`=`auth`, `G`=`guest`, `S`=`ResolveSchoolContext`, `P:x`=`EnsurePermission:x`, `T`=`EnsureTenantSignedStorage`. Rows 1–42 retain the original audit numbering; rows 43–44 are the frontend routes added by PR #64 and now gated in PR #72; rows 45–46 are the replacement private-storage routes. Machine-readable evidence: `/home/praisetechzw/b04-audit-evidence/followup-routes-app.json` and `.tsv`.

| # | Method | Path | Controller / action | Module | Auth / permission | Tenant and ownership | Tests linked | Risk; required check / audit result |
|---:|---|---|---|---|---|---|---|---|
| 1 | GET/HEAD | `/` | Closure → welcome view | Foundation | `W` | No school data | `FoundationTest` | Public intended; guest 200 verified. |
| 2 | GET/HEAD | `api/academic-calendar/validation` | `AcademicCalendarController@validateCalendar` | Calendar | `W,A,S,P:academic_calendar.view` | `school_id` rejected; year scoped to current school | `AcademicCalendarIsolationTest`, `B04RemainingGapsTest` | Fixed F04. Guest/no-role/role_viewer denied; foreign-year 404 covered. |
| 3 | PUT/PATCH | `api/academic-periods/{academicPeriod}` | `AcademicCalendarController@updatePeriod` | Calendar | `W,A,S,P:academic_calendar.manage` (fixed) | `scopedPeriod()`; domain integrity guard | `AcademicCalendarIsolationTest`, `AcademicCalendarPermissionTest` | Fixed F01. Member/no-role/role_viewer 403, editor/admin 200, foreign 404, client `school_id` 422 covered. |
| 4 | GET/HEAD | `api/academic-structure/class-offerings` | `AcademicStructureController@offerings` | Structure | `W,A,S,P:academic_structure.view` | `forCurrentSchool()`; validated year filter | `AcademicStructureIsolationTest`, `AcademicStructurePermissionTest` | Guest 401, viewer/member 403, cross-school no leak covered. |
| 5 | POST | `api/academic-structure/class-offerings` | `AcademicStructureController@storeOffering` | Structure | `W,A,S,P:academic_structure.manage` | `assertCurrent()`; `school_id` prohibited; parent scoping | `AcademicStructureIsolationTest`, `AcademicStructurePermissionTest` | Create with `school_id` 422; foreign class/year generic 422; member 403. |
| 6 | PUT/PATCH | `api/academic-structure/class-offerings/{classOffering}` | `AcademicStructureController@updateOffering` | Structure | `W,A,S,P:academic_structure.manage` | `scopedOffering()`; domain integrity guard | `AcademicStructureIsolationTest`, `AcademicStructurePermissionTest` | Update `school_id` 422, foreign school 403, foreign offering 404 without data leak, member 403. |
| 7 | PUT/PATCH | `api/academic-structure/classes/{classDefinition}` | `AcademicStructureController@updateClass` | Structure | `W,A,S,P:academic_structure.manage` | `scopedClass()`; parent guard | `AcademicStructureIsolationTest` | Guest/foreign/member checks covered. |
| 8 | GET/HEAD | `api/academic-structure/classes/{classDefinition}/streams` | `AcademicStructureController@streams` | Structure | `W,A,S,P:academic_structure.view` | `scopedClass()` + `forCurrentSchool()` | `AcademicStructureIsolationTest` | Foreign parent 404; no cross-school stream leak. |
| 9 | POST | `api/academic-structure/classes/{classDefinition}/streams` | `AcademicStructureController@storeStream` | Structure | `W,A,S,P:academic_structure.manage` | `scopedClass()`; parent guard | `AcademicStructurePermissionTest` | Member 403; editor 201; foreign parent generic 422. |
| 10 | GET/HEAD | `api/academic-structure/grade-levels` | `AcademicStructureController@allGradeLevels` | Structure | `W,A,S,P:academic_structure.view` | `forCurrentSchool()` | `AcademicStructureIsolationTest` | Cross-school no leak; viewer 403. |
| 11 | PUT/PATCH | `api/academic-structure/grade-levels/{gradeLevel}` | `AcademicStructureController@updateGrade` | Structure | `W,A,S,P:academic_structure.manage` | `scopedGrade()`; parent guard | `AcademicStructureIsolationTest` | Foreign 404; member 403. |
| 12 | GET/HEAD | `api/academic-structure/grade-levels/{gradeLevel}/classes` | `AcademicStructureController@classes` | Structure | `W,A,S,P:academic_structure.view` | `scopedGrade()` + `forCurrentSchool()` | `AcademicStructureIsolationTest` | Foreign parent 404; viewer 403. |
| 13 | POST | `api/academic-structure/grade-levels/{gradeLevel}/classes` | `AcademicStructureController@storeClass` | Structure | `W,A,S,P:academic_structure.manage` | `scopedGrade()`; parent guard | `AcademicStructurePermissionTest` | Member 403; editor 201; foreign parent generic 422. |
| 14 | GET/HEAD | `api/academic-structure/levels` | `AcademicStructureController@levels` | Structure | `W,A,S,P:academic_structure.view` | `forCurrentSchool()` | `AcademicStructureIsolationTest`, `AcademicStructurePermissionTest` | Guest 401; viewer/member 403; cross-school no leak. |
| 15 | POST | `api/academic-structure/levels` | `AcademicStructureController@storeLevel` | Structure | `W,A,S,P:academic_structure.manage` | `assertCurrent()`; `school_id` prohibited | `AcademicStructureIsolationTest`, `AcademicStructurePermissionTest` | Client tenant field rejected; member 403. |
| 16 | PUT/PATCH | `api/academic-structure/levels/{educationalLevel}` | `AcademicStructureController@updateLevel` | Structure | `W,A,S,P:academic_structure.manage` | `scopedLevel()` | `AcademicStructureIsolationTest` | Foreign 404; member 403. |
| 17 | GET/HEAD | `api/academic-structure/levels/{educationalLevel}/grade-levels` | `AcademicStructureController@gradeLevels` | Structure | `W,A,S,P:academic_structure.view` | `scopedLevel()` + `forCurrentSchool()` | `AcademicStructureIsolationTest` | Foreign parent 404; viewer 403. |
| 18 | POST | `api/academic-structure/levels/{educationalLevel}/grade-levels` | `AcademicStructureController@storeGrade` | Structure | `W,A,S,P:academic_structure.manage` | `scopedLevel()`; parent guard | `AcademicStructurePermissionTest` | Member 403; editor 201; foreign parent generic 422. |
| 19 | PUT/PATCH | `api/academic-structure/streams/{stream}` | `AcademicStructureController@updateStream` | Structure | `W,A,S,P:academic_structure.manage` | `scopedStream()` | `AcademicStructureIsolationTest` | Foreign 404; member 403. |
| 20 | GET/HEAD | `api/academic-years` | `AcademicCalendarController@years` | Calendar | `W,A,S,P:academic_calendar.view` | `school_id` rejected; `forCurrentSchool()` | `AcademicCalendarIsolationTest`, `B04RemainingGapsTest` | Fixed F04. Guest and role_viewer denied; cross-school no leak covered. |
| 21 | POST | `api/academic-years` | `AcademicCalendarController@storeYear` | Calendar | `W,A,S,P:academic_calendar.manage` (fixed) | `assertCurrent()`; `school_id` prohibited; school lock | `AcademicCalendarIsolationTest`, `AcademicCalendarPermissionTest` | Fixed F01. Member 403, editor/admin 201, client `school_id` 422, null `school_id` ignored. |
| 22 | GET/HEAD | `api/academic-years/current` | `AcademicCalendarController@current` | Calendar | `W,A,S,P:academic_calendar.view` | `school_id` rejected; current year scoped | `AcademicCalendarIsolationTest`, `B04RemainingGapsTest` | Fixed F04. Guest 401, role_viewer 403, foreign selection 403, null-current covered. |
| 23 | PUT/PATCH | `api/academic-years/{academicYear}` | `AcademicCalendarController@updateYear` | Calendar | `W,A,S,P:academic_calendar.manage` (fixed) | `scopedYear()`; `assertCurrent()`; integrity guard | `AcademicCalendarIsolationTest`, `AcademicCalendarPermissionTest` | Fixed F01. Member 403, editor/admin 200, foreign 404, `school_id` 422 covered. |
| 24 | GET/HEAD | `api/academic-years/{academicYear}/periods` | `AcademicCalendarController@periods` | Calendar | `W,A,S,P:academic_calendar.view` | `scopedYear()` + `forCurrentSchool()` | `AcademicCalendarIsolationTest`, `B04RemainingGapsTest` | Fixed F04. Guest 401; role_viewer 403; foreign year 404. |
| 25 | POST | `api/academic-years/{academicYear}/periods` | `AcademicCalendarController@storePeriod` | Calendar | `W,A,S,P:academic_calendar.manage` (fixed) | Route year authoritative; `scopedYear()`; `school_id` rejected | `AcademicCalendarIsolationTest`, `AcademicCalendarPermissionTest` | Fixed F01. Member 403, editor/admin 201, foreign year no leak. |
| 26 | POST | `api/auth/login` | `Auth\LoginController@store` | Auth | `W`, throttled; intentionally public | No school context by design; client `school_id` cannot grant membership | `AuthenticationTest` | Public intended; throttling, generic failure and tenant-input no-widening covered. |
| 27 | POST | `api/auth/logout` | `Auth\LogoutController@destroy` | Auth | `W,A` | No school context | `AuthenticationTest` | Guest 401 covered. |
| 28 | GET/HEAD | `api/auth/me` | `Auth\MeController@show` | Auth | `W,A` | Allow-listed fields | `AuthenticationTest` | Guest 401; no school/remember-token leak covered. |
| 29 | GET/HEAD | `api/role-assignments` | `RoleAssignmentController@index` | Authorization | `W,A,S,P:roles.view` | `assertCurrent()`; client `school_id` rejected; domain double-check | `RolesPermissionsTest` | Guest/no-role 403, viewer 200, cross-school no leak. |
| 30 | POST | `api/role-assignments` | `RoleAssignmentController@store` | Authorization | `W,A,S,P:roles.manage` | `assertCurrent()`; `school_id` prohibited; same-school actor required; no escalation | `RolesPermissionsTest` | Cross-school user 422, non-escalation, editor 403 covered. |
| 31 | DELETE | `api/role-assignments/{roleAssignment}` | `RoleAssignmentController@destroy` | Authorization | `W,A,S,P:roles.manage` | Client `school_id` rejected; school-scoped removal | `RolesPermissionsTest` | Viewer/editor 403; cross-school removal denied. |
| 32 | GET/HEAD | `api/school` | `SchoolContextController@current` | School context | `W,A,S,P:school.view` | `assertCurrent()`; tenant-aware headers | `SchoolIsolationTest`, `B04RemainingGapsTest` | Fixed F04. Roleless member 403; member 200. |
| 33 | GET/HEAD | `api/school-settings` | `SchoolSettingsController@show` | School settings | `W,A,S,P:school_settings.view` | `school_id` rejected; scoped resolve | `SchoolSettingsIsolationTest`, `B04RemainingGapsTest` | Fixed F03. Member read 200; role_viewer 403. |
| 34 | PUT/PATCH | `api/school-settings` | `SchoolSettingsController@update` | School settings | `W,A,S,P:school_settings.manage` | `assertCurrent()`; `school_id` and secret fields prohibited | `SchoolSettingsIsolationTest`, `B04RemainingGapsTest` | Fixed F03. Member 403; editor 200. |
| 35 | GET/HEAD | `api/school-settings/revisions` | `SchoolSettingsController@revisions` | School settings | `W,A,S,P:school_settings.view` | `school_id` rejected; scoped revisions | `SchoolSettingsIsolationTest`, `B04RemainingGapsTest` | Fixed F03. Member read 200; role_viewer 403. |
| 36 | GET/HEAD | `api/school/identifiers` | `SchoolContextController@identifiers` | School context | `W,A,S,P:school_identifiers.view` | `assertCurrent()`; `forCurrentSchool()` | `SchoolIsolationTest`, `B04RemainingGapsTest` | Fixed F04. Member 200; role_viewer 403; foreign selection 403. |
| 37 | GET/HEAD | `app` | `App\Livewire\Dashboard` | Frontend | `W,A,S,P:school.view` | Data provider uses server context and permission-filtered payloads | `FrontendFoundationTest`, `B04RemainingGapsTest` | Guest redirect, role viewer dashboard allowed, provider payloads filtered; permission-aware nav links. |
| 38 | GET/HEAD | `app/academic-calendar` | `Frontend\AcademicCalendarViewController@index` | Frontend | `W,A,S,P:academic_calendar.view` | Years/periods scoped to server context | `FrontendFoundationTest`, `CalendarAdministrationTest`, `B04RemainingGapsTest` | Fixed F04/F05. Guest/role_viewer 403; manage link hidden from members. |
| 39 | GET/HEAD | `app/academic-calendar/manage` | `App\Livewire\CalendarAdministration` | Frontend | `W,A,S,P:academic_calendar.manage` | Persistent school context; gateway scopes records | `CalendarAdministrationTest` | Fixed F01. Member 403, editor 200, unowned school selection 403, no foreign name in response. |
| 40 | GET/HEAD | `app/modules/{module}` | `Frontend\ModuleNoticeController@show` | Frontend | `W,A,S`; membership-only | No school data rendered | `FrontendFoundationTest` | Guest redirect/no-membership covered; no tenant test required. |
| 41 | GET/HEAD | `app/school` | `Frontend\SchoolProfileController@index` | Frontend | `W,A,S,P:school.view` | Identifiers scoped via permission-aware data provider | `FrontendFoundationTest`, `B04RemainingGapsTest` | Fixed F04. Cross-school no leak; identifiers hidden from role_viewer. |
| 42 | GET/HEAD | `login` | `Frontend\LoginController@show` | Frontend | `W,G` | No school context | `FrontendFoundationTest` | Public guest-only intended; authenticated redirect covered. |
| 43 | GET/HEAD | `app/school-settings` | `App\Livewire\SchoolSettings` | Frontend | `W,A,S,P:school_settings.view` | Gateway reads use server context; writes recheck `school_settings.manage` | `SchoolSettingsScreenTest`, `B04RemainingGapsTest` | Fixed F10. Roleless/role_viewer 403; member read-only; editor can save; save controls hidden from non-managers. |
| 44 | GET/HEAD | `app/roles` | `App\Livewire\RoleAssignments` | Frontend | `W,A,S,P:roles.view` | Component self-enforces view; domain actions self-enforce manage | `RoleAssignmentsScreenTest`, `B04RemainingGapsTest` | Fixed F10. Roleless/member 403; role_viewer/admin 200; nav link hidden from member; component hides assignments without view. |
| 45 | GET/HEAD | `storage/{path}` | `TenantFileController@show` | File storage | `W,A,S,T` | Signature binds `tenant_id` and canonical `schools/{id}/` path | `TenantSignedStorageTest` | Fixed F07. Signed read succeeds; unsigned/tampered/cross-school/dot-segment paths 403. |
| 46 | PUT | `storage/{path}` | `TenantFileController@store` | File storage | `W,A,S,T` | Upload flag bound to signature and HTTP method | `TenantSignedStorageTest` | Fixed F07. Signed upload succeeds; download signature cannot upload and vice versa. |

### Framework/vendor runtime routes (outside application route files)

`php artisan route:list --json` showed **52** registrations in total; the 6 additional entries are Livewire JavaScript/map assets (2), `livewire/update`, `livewire/upload-file`, `livewire/preview-file/{filename}`, and health `/up`.

- `livewire/update` has only `web` at route level. The application persists `ResolveSchoolContext` and `EnsurePermission` for Livewire updates, so the originating route's permission gate is replayed. Calendar, settings, and role gateways/domain actions also self-enforce their write permissions. F06 is closed for the current surface; every new Livewire write must still use the same explicit check.
- Laravel's unauthenticated framework signed-storage routes are disabled (`local.serve => false`). The 2 replacement routes are application-owned, authenticated, school-bound, and listed as rows 45–46 above.
- Livewire upload/preview, asset, and health routes remain framework-owned. No application tenant-file usage of the Livewire upload/preview routes was found; `TenantFileStorage` is the supported path for private tenant files.

## 3. Permission and role matrix

### Configured permissions and effective enforcement

| Permission | Configured roles | Enforcement after B-04 / PR #72 | Status |
|---|---|---|---|
| `school.view` | `school_member`, `school_editor`, `role_viewer`, `school_admin` (`*`) | `/api/school`, `/app`, `/app/school`, and provider `school()` | Fixed F04 |
| `school_identifiers.view` | `school_member`, `school_editor`, `school_admin` (`*`) | `/api/school/identifiers` and provider `identifiers()` | Fixed F04 |
| `school_settings.view` | `school_member`, `school_editor`, `school_admin` (`*`) | `GET /api/school-settings`, `GET /api/school-settings/revisions`, `GET /app/school-settings` | Fixed F03/F10 |
| `school_settings.manage` | `school_editor`, `school_admin` (`*`) | `PUT|PATCH /api/school-settings` and `DomainSchoolSettingsGateway::update()` | Fixed F03/F10 |
| `academic_calendar.view` | `school_member`, `school_editor`, `school_admin` (`*`) | 5 calendar read routes (`validation`, `years`, `current`, `periods`, frontend calendar) and provider year/period reads | Fixed F04 |
| `academic_calendar.manage` | `school_editor`, `school_admin` (`*`) | 4 calendar API write routes, `/app/academic-calendar/manage`, and `DomainCalendarManagementGateway` writes | Fixed F01 |
| `academic_structure.view` | `school_member`, `school_editor`, `school_admin` (`*`) | 6 structure read routes | Existing, test coverage extended |
| `academic_structure.manage` | `school_editor`, `school_admin` (`*`) | 10 structure write routes | Existing, test coverage extended |
| `roles.view` | `role_viewer`, `school_admin` (`*`) | `GET api/role-assignments` and `GET /app/roles` | Existing / F10 frontend gate |
| `roles.manage` | `school_admin` (`*`) | Role create/delete plus domain non-escalation checks | Existing |

`school_admin` uses the configured wildcard `*`; `SchoolPermissions` resolves only permissions present in the catalogue and requires an active assignment in the current school. Unknown roles/permissions fail closed. There is no direct user-permission table. `SchoolPermissions::authorize(User, School, permission)` is the fail-closed contract for callers outside route middleware; a regression proves it throws without the permission and succeeds after the role is granted.

Navigation links in `config/frontend.php` now carry optional `permission` keys mirroring the route gates. Both the app shell (`app-shell.blade.php`) and dashboard (`App\Livewire\Dashboard`) filter those links with the same `SchoolPermissions` service. This is presentation only; the route and gateway checks remain authoritative.

### Documented role impact

This is a real access change and requires an owner/rollout decision before deployment:

- `school_member` reads school settings, settings revisions, school identifiers, school context, and calendar records. Previously those endpoints were membership-only, so the surface is clearer but a roleless member no longer has implicit access.
- `school_editor` has `school_settings.manage` in addition to its existing calendar and structure write permissions.
- `role_viewer` keeps the dashboard, school profile name, and role-assignment read, and has `school.view`; it does **not** gain settings, identifier, or calendar reads. Sensitive provider payloads are empty/null for those actions.
- A user with school membership but no role gets no permission-gated reads. Existing roleless users need an explicit role-assignment action before rollout; no database migration is required because role contents are configuration-only.
- `school_admin` is unchanged because the wildcard already covered future permissions.

### Role label correction

`school_editor` was labelled **School calendar editor** while its configured permissions include `academic_structure.manage`. The label is **School editor**. The role slug, permission list, assignment records, and route behavior are unchanged. No data migration is required; only display text changes.

## 4. Tenant-isolation evidence

No confirmed cross-school IDOR or tenant-escalation defect was found in the reviewed HTTP, Livewire, frontend-read, role, settings, calendar, structure, or private-file paths. Evidence:

- `ResolveSchoolContext` derives all authorisation from authenticated memberships. `X-School-Id` and `school_id` can only select among those memberships; unauthorised, malformed, conflicting, and empty identifiers fail closed (400/403).
- Multi-school users must select a school. Livewire remembers the selection in session and revalidates it against current memberships on every update; `auth`, `ResolveSchoolContext`, and `EnsurePermission` are persistent middleware.
- `SchoolContext` is cleared after normal requests; Livewire defers clearing until request termination so persistent middleware can revalidate.
- School-owned models use explicit `forCurrentSchool()` / `forSchool()` scopes and `assertCurrent()`; foreign route IDs return 404 or generic 422 and do not reveal foreign names/metadata.
- `prohibited` FormRequest rules reject non-empty client `school_id`; null/empty values are ignored and cannot change server-resolved ownership.
- Role assignment is school-scoped and non-escalating: an actor cannot grant a role containing permissions they do not already hold in that school, and a role assigned in another school grants nothing.
- Tenant-aware `Cache-Control: no-store` and `Vary` headers are asserted on JSON calendar, school, and settings responses.
- Sensitive provider data is permission-filtered: a `role_viewer` can load the dashboard and school profile name but receives no identifier values and no academic year/period records.
- Private files use `schools/{school_id}/...` paths and signed URLs carrying a separate `tenant_id` parameter. `tenant_id` avoids being interpreted as a client school selector by `ResolveSchoolContext`. `TenantFileStorage::assertTenantPath()` rejects prefixes outside the current school, empty/dot/dot-dot segments, backslashes, NUL bytes, and invalid characters. `EnsureTenantSignedStorage` requires authentication, an authorised school context, a valid signature, a matching signed tenant ID, canonical path validation, and an exact method/flag match (`upload=1` only for `PUT`).
- `config/filesystems.php` sets `local.serve => false`, so Laravel's unauthenticated framework signed-storage routes are not registered. The replacement routes are gated by `auth`, `school.context`, and `EnsureTenantSignedStorage`.

Executed tenant regressions include: cross-school calendar year/period 404, foreign offering update 404 with no leak, foreign offering class/year references 422, unowned `X-School-Id` on the calendar management screen 403 without the foreign school name, role in another school grants no permission, empty/null school IDs do not widen access, roleless/member/role_viewer write denials, cross-school signed-storage replay 403, unsigned/tampered signature 403, download-signature upload misuse 403, upload-signature download misuse 403, path traversal signing failure, and middleware rejection of a signed dot-segment path.

## 5. Security findings

### F01 — High — fixed: calendar writes had no permission gate

Baseline route middleware on the 4 calendar write API routes was only `web,auth,school.context`. `academic_calendar.manage` existed in the catalogue but was never enforced. A roleless school member, `school_member`, or `role_viewer` could create/update academic years and periods and could use the frontend management screen and gateway.

**Fix:** reuse the existing architecture only — add `EnsurePermission:academic_calendar.manage` to the 4 write routes and the management screen, and add an internal `SchoolPermissions::has(...)` assertion in `DomainCalendarManagementGateway` for all gateway writes. No new permission, policy, gate, or domain rewrite was introduced. Shipped in PR #62/#63 and retained through reconciliation.

**Regression evidence:** `AcademicCalendarPermissionTest` denies all 4 write routes for no role / `school_member` / `role_viewer`, allows `school_editor` / `school_admin`, and denies a role assigned only in another school. `CalendarAdministrationTest` denies the management screen, a Livewire write, and a gateway write.

### F02 — Medium — fixed: misleading `school_editor` label

The role label claimed calendar-only scope while the role also grants Academic Structure management. Changed to **School editor**; no permission or assignment change.

### F03 — Medium — fixed: school settings were membership-only

There was no settings permission in `config/authorization.php`, and all 3 settings API routes relied on school membership alone. Tenant isolation was sound, but any school member could read settings/revisions and update settings.

**Fix:** add `school_settings.view` and `school_settings.manage` to the existing catalogue; grant view to `school_member`, `school_editor`, and admin wildcard, and manage to `school_editor` and admin wildcard. Apply `EnsurePermission` on the 3 routes and repeat the write check in `DomainSchoolSettingsGateway` using the server-resolved school. The role impact is documented in section 3.

**Regression evidence:** `B04RemainingGapsTest` proves member read/revisions 200, member write 403, editor write 200 and persisted to the correct school, and role_viewer read/write/revisions 403. `SchoolSettingsScreenTest` proves non-managers do not see save controls and a member Livewire `save` returns the denial message without writing.

### F04 — Medium — fixed: catalogued read permissions were not enforcement boundaries

`school.view`, `school_identifiers.view`, and `academic_calendar.view` were configured but not applied to the relevant school-context, identifier, calendar-read, and read-only frontend routes. `school_identifiers.view` was not granted to any configured non-admin role.

**Fix:** grant identifier view to `school_member` and `school_editor`, apply the catalogued read permissions to all 10 affected routes, repeat the checks in `SchoolContextController`, `AcademicCalendarController`, and `ServerFrontendDataProvider`, and filter provider payloads by permission. No second permission system was introduced.

**Regression evidence:** `B04RemainingGapsTest` covers roleless `/api/school` 403, member reads 200, role_viewer identifier/calendar/read-screen 403, missing identifier values and academic-year records in role_viewer frontend payloads, and dashboard/profile name visibility under `school.view`.

### F05 — Low — fixed: manage link shown to unauthorized members

The read-only calendar Blade view rendered the management link for any member. The target route and gateway already returned 403, so this was a UX/informational issue rather than an authorization bypass.

**Fix:** pass `$canManageCalendar` from the controller using `SchoolPermissions` and render the link only when `academic_calendar.manage` is present.

**Regression evidence:** `B04RemainingGapsTest` asserts a member does not see “Manage calendar” or the management route URL, while an editor does.

### F06 — Low — fixed for current surface: Livewire update route had no permission middleware

`livewire/update` is framework-owned and only has `web` at route level. Auth and school context were replayed by Livewire, but `EnsurePermission` was not persistent.

**Fix:** register `ResolveSchoolContext` and `EnsurePermission` with `Livewire::addPersistentMiddleware()`, so the originating route's permission middleware is replayed on component updates. The existing gateway/domain self-enforcement remains as defence in depth.

**Regression evidence:** `B04RemainingGapsTest` asserts both middleware are in `Livewire::getPersistentMiddleware()`; `CalendarAdministrationTest`, `RoleAssignmentsScreenTest`, and `SchoolSettingsScreenTest` deny unauthorized Livewire actions and hide unauthorized data/controls.

### F07 — Low — fixed: signed framework file routes were not tenant-bound

Laravel's framework signed-storage routes were unauthenticated and bound only to a path. No application tenant-file usage was found, but the routes were an unsafe default for future private files.

**Fix:** disable framework `serve` routes on the private disk, add `TenantFileStorage`, `EnsureTenantSignedStorage`, and `TenantFileController`, and register authenticated `storage.local` / `storage.local.upload` routes behind `auth`, `school.context`, and the tenant-signature guard. The signature binds a `tenant_id` separate from the client's `school_id`, and the path must start with the current school's canonical directory.

**Regression evidence:** `TenantSignedStorageTest` covers signed read/upload success, cross-school replay, unsigned and tampered URLs, download/upload flag misuse, traversal at signing time, dot segments at middleware time, and route middleware membership.

### F08 — Informational — no cross-school IDOR confirmed

Tested offering-by-ID update, foreign offering class/year references, foreign calendar IDs, foreign school selection, cross-school role assignment, frontend unowned selection, and signed-storage replay/tampering. All failed closed without foreign data in responses. The previously untested offering `school_id` prohibition is explicitly covered for create and update.

### F09 — Informational — architecture boundary preserved

No Gate/Policy definitions exist; authorization remains the existing middleware + `SchoolPermissions` + domain-guard architecture. No RBAC rewrite, duplicate permission system, or unrelated school workflow was added.

### F10 — Medium — fixed in PR #72: PR #64 frontend routes were membership-only

After PRs #62/#63 merged, PR #64 added `/app/school-settings` and `/app/roles` with only `auth,school.context`. The settings screen could render settings/revisions for any school member even though its API was permission-gated. The role screen self-enforced view in its component, but the route itself did not use the catalogued `roles.view` gate. Navigation links to both screens were rendered for members regardless of route permission.

**Fix:** apply `EnsurePermission:school_settings.view` to `/app/school-settings` and `EnsurePermission:roles.view` to `/app/roles`; hide save controls from non-managers and self-enforce `school_settings.manage` in the gateway; add optional `permission` keys to the navigation catalogue and filter them through `SchoolPermissions` in both the app shell and dashboard. No new route or permission was invented.

**Regression evidence:** `SchoolSettingsScreenTest` proves roleless/role_viewer 403, member read-only UI, editor write, and member Livewire denial. `RoleAssignmentsScreenTest` proves roleless/member 403, role_viewer/admin 200, component-level data hiding without view, and domain manage denial. `B04RemainingGapsTest` proves nav links are hidden/shown per permission and route permission middleware is replayed on Livewire updates.

### F11 — Low — fixed in PR #72: duplicate role fixture masked full-suite state

A reconciled fixture in `FrontendFoundationTest.php` assigned `school_member` and then attempted to force-create the same role, producing a unique-constraint failure in the full suite after `grantSchoolRole()` was introduced. This was a test-fixture regression, not a production authorization defect. The duplicate assignment was removed; the full suite is green.

## 6. Tests added or changed

The final branch reports **329 passed / 1372 assertions** in the full suite. The focused security/frontend suites report **46 passed / 119 assertions**. The original audited `main` was recorded earlier at 243 / 990; no remote CI number is claimed for PR #72.

| File | Added regression coverage |
|---|---|
| `tests/Feature/Domains/AcademicCalendar/AcademicCalendarPermissionTest.php` (new) | Calendar writes denied for no role / `school_member` / `role_viewer`; allowed for `school_editor` / `school_admin`; cross-school role grants nothing. |
| `tests/Feature/Domains/AcademicCalendar/AcademicCalendarIsolationTest.php` | Calendar year/period updates reject client `school_id`; null `school_id` cannot widen ownership; fixtures carry a calendar-capable role. |
| `tests/Feature/Domains/AcademicStructure/AcademicStructureIsolationTest.php` | Offering create/update `school_id` rejection; foreign school context on update 403; foreign offering update 404 without leak; foreign class/year IDs generic 422; null `school_id` ignored. |
| `tests/Feature/Domains/AcademicStructure/AcademicStructurePermissionTest.php` | Nested grade/class/stream writes and offering create/update denied for `school_member`, allowed for `school_editor`. |
| `tests/Feature/Domains/Authorization/B04RemainingGapsTest.php` (new) | 19 tests: settings view/manage denials and grants; read-gate denials on school, identifiers, validation, years, current, periods, and calendar screens; role_viewer payload filtering; manage-link visibility; permission-aware nav visibility for member/role_viewer; Livewire persistent middleware; `SchoolPermissions::authorize()` fail-closed contract. |
| `tests/Feature/Domains/Authorization/TenantSignedStorageTest.php` (new) | 8 tests: signed private read/upload success; cross-school replay; unsigned/tampered tenant ID; download/upload flag misuse; path traversal and dot segments; route middleware contract. |
| `tests/Feature/Domains/School/SchoolIsolationTest.php` | Empty header/query school IDs fail closed without widening access; fixtures carry the new read permissions. |
| `tests/Feature/Domains/SchoolSettings/SchoolSettingsIsolationTest.php` | Existing isolation suite grants settings roles so the permission gate does not mask tenant assertions. |
| `tests/Feature/Domains/Auth/AuthenticationTest.php`, `tests/Feature/Domains/Authorization/RolesPermissionsTest.php`, `tests/Pest.php` | Fixtures use the shared `grantSchoolRole()` helper and carry permissions required by gated reads. |
| `tests/Feature/Frontend/FrontendFoundationTest.php` | Fixtures aligned with gated reads and duplicate role assignment removed after reconciliation. |
| `tests/Feature/Frontend/CalendarAdministrationTest.php` | Management screen 403 for non-editor; Livewire write denial; gateway permission enforcement; unowned school selection 403 with no foreign school name. |
| `tests/Feature/Frontend/SchoolSettingsScreenTest.php` | 9 tests: auth, verified defaults, contract-field writes/provenance, revisions, validation errors, role gate, read-only UI, member Livewire denial. |
| `tests/Feature/Frontend/RoleAssignmentsScreenTest.php` | 10 tests: auth, verified fields, role assignment/removal, non-manager denial, `roles.view` route gate matrix, component-level view hiding. |
| `tests/Feature/Domains/AuditTrail/AuditTrailIsolationTest.php` | Existing audit fixtures use `school_editor` because calendar writes require `academic_calendar.manage`. |

## 7. Verification evidence

Commands were executed locally in the isolated worktree `/home/praisetechzw/.local/share/opencode/worktree/b04-f03-f07-followup` after merging `origin/main` `81cba1d`. Final results only are listed here; earlier superseded logs remain in the evidence directory for traceability.

| Command | Result | Evidence |
|---|---|---|
| `make verify COMPOSE="docker compose -p b04-audit-r -f … -f /home/praisetechzw/b04-audit-evidence/compose-override.yml"` | Exit 0; Pint 224 files clean; PHPStan `[OK] No errors`; Composer audit `No security vulnerability advisories found`; **329 passed / 1372 assertions** | `/home/praisetechzw/b04-audit-evidence/followup-make-verify.log` |
| Focused security/frontend suites (`B04RemainingGapsTest`, `TenantSignedStorageTest`, `SchoolSettingsScreenTest`, `RoleAssignmentsScreenTest`) | Exit 0; **46 passed / 119 assertions** | `/home/praisetechzw/b04-audit-evidence/followup-focused-tests.log` |
| `php artisan route:list --except-vendor --json` | 46 app registrations; 38 permission gates; 2 tenant-storage guards | `/home/praisetechzw/b04-audit-evidence/followup-routes-app.json`, `.tsv` |
| `php artisan route:list --json` | 52 total registrations; 6 framework/package routes; no framework `storage/{path}` route | `/home/praisetechzw/b04-audit-evidence/followup-routes-all.json`, `.tsv` |
| `docker compose -p b04-audit-r -f … config -q` | Exit 0 | Re-run after final merge; override at `/home/praisetechzw/b04-audit-evidence/compose-override.yml` |
| `git diff --check` | Exit 0 | Command output captured after final merge |
| `git ls-remote origin refs/heads/main` | `81cba1d84752b69d35a3ef91d407382c116355f5` at final reconciliation | Command output captured in this audit session |
| `git push -u origin audit/b04-f03-f07-followup` | New branch pushed; no force-push | Command output captured in this audit session |
| `gh pr create --base main --head audit/b04-f03-f07-followup` | Created PR #72 | `https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/72` |
| `gh pr view 72 --json …` | `state: OPEN`, `mergedAt: null` at creation; final head re-checked after the documentation commit | Command output captured in this audit session |
| `command -v mysql` | Not available; MySQL integration not run | Command output captured in this audit session |
| `gstack-cso` static run `1795806574566-c85a630b4c8088a1` | `completeness: partial`; no findings; static snapshot only | Tool run record |

No GitHub Actions check-run result, review approval, deployment, or merge is claimed for PR #72 in this document. PRs #62 and #63 merge states were verified with `gh pr list --state all`.

## 8. Risks, owner actions and status

| ID | Risk | Likelihood / impact | Status | Required owner action |
|---|---|---|---|---|
| F03 | Any school member could read/update school settings | Medium / high | **Fixed and regression-tested** | Confirm the documented `school_member` read / `school_editor` write grants before rollout. |
| F04 | Catalogued read permissions were not enforced; identifier visibility was undecided | Medium / medium | **Fixed and regression-tested** | Confirm identifier visibility for members and the roleless-member denial; assign roles to existing users before rollout. |
| F05 | Manage link visible to users who receive 403 | High / low | **Fixed and regression-tested** | No action required beyond review. |
| F06 | Future Livewire actions could bypass route permission gates | Medium / high | **Fixed for current surface** | Keep using gateway/domain authorization for every new Livewire write. |
| F07 | Future tenant files could be exposed via signed routes | Low / high if introduced | **Fixed for application-owned storage** | Use `TenantFileStorage` for private files; do not expose Livewire vendor upload/preview for tenant files without a separate tenant-binding review. |
| F10 | PR #64 settings/role screens were membership-only | Medium / medium | **Fixed and regression-tested** | Review the new `roles.view` route gate and navigation mirroring; confirm `role_viewer` should keep role-screen read access. |
| F11 | Duplicate role fixture broke the full suite | Low / low | **Fixed** | No action required beyond review. |
| R-01 | Roleless school members lose previously implicit read access | Medium / medium | **Open rollout action** | Assign `school_member` (or the appropriate configured role) to existing memberships before deployment. No schema migration needed. |
| F01 | Roleless calendar writes | High / high | **Fixed and regression-tested** | Human review of role impact; no migration required. |
| F02 | Misleading role label | Medium / low | **Fixed** | Confirm display text in UI copy if referenced. |

Deferred, not claimed as fixed here: MFA, separation of duties, time-bounded delegation, periodic role review, and permission-change audit events. They require separate product/security decisions and are outside this narrow remediation.

## 9. Integration tracker and traceability

- `docs/github/integration-tracker.md` is reconciled to `origin/main` `81cba1d84752b69d35a3ef91d407382c116355f5` and the observed remote PR states: #62–#68 MERGED; #69–#71 OPEN; #72 OPEN and unmerged.
- Audited baseline: `c21d0a90530cc2764853e6b3eabb277501da696b`.
- Reconciled baseline: `81cba1d84752b69d35a3ef91d407382c116355f5`.
- Change branch: `audit/b04-f03-f07-followup` (merged `origin/main`; no rebase of shared history and no force-push).
- Review PR: [#72](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/72) — **OPEN, unmerged**; do not merge before review and remote check evidence exist.
- Route evidence: `/home/praisetechzw/b04-audit-evidence/followup-routes-app.json`, `.tsv`, `followup-routes-all.json`, `.tsv`.
- Verification evidence: `/home/praisetechzw/b04-audit-evidence/followup-make-verify.log`, `followup-focused-tests.log`, and `compose-override.yml`.
- This report is the authoritative B-04 route/permission matrix, role-impact record, unresolved-actions list, and risk register.

### Handoff to Audit Coverage agent

Please verify that the 46-route matrix, 38 explicit permission gates, 2 tenant-signed storage guards, F03–F07 and F10 fixes, explicit role grants, rollout action R-01, and the risk register match your coverage record. Keep PR #72 open and unmerged; do not report CI, review, deployment, or merge status until fresh remote command output exists.
