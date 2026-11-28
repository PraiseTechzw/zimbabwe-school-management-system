# B-04 authorization and tenant-isolation audit

**Task:** B-04 backend authorization / tenant-isolation audit and narrow remediation
**Audited baseline:** `c21d0a90530cc2764853e6b3eabb277501da696b` (`local main` and `origin/main` at audit start)
**Current `origin/main` observed before reporting:** `6844b4289af6c5446b80d897f73de39d4bb6aa7b` (PR #61 final Academic Structure evidence; no backend code changed)
**Working branch:** `audit/b04-authorization-tenant-isolation`
**Evidence class:** executed local application tests and command output; static route/source review. Remote GitHub Actions results are **not** claimed in this report.
**Handoff:** Audit Coverage agent / human reviewer.

## 1. Scope and method

This audit inventories every application-owned route and checks authentication, permission gates, server-resolved school context, ownership checks, role assignment controls, tenant isolation, and sensitive-data exposure. It reviews `routes/*.php`, middleware, `config/authorization.php`, controllers, FormRequests, Livewire components, frontend gateways, domain guards, private file storage, and the linked Pest suites.

Evidence sources:

- `php artisan route:list --except-vendor --json` captured before and after the fix.
- Source review of middleware, controllers, FormRequests, domain actions, Livewire component and gateway, and the private-disk configuration.
- Executed focused Pest security suites and the full `make verify` gate in the isolated Compose project `b04-audit-r`.
- A `gstack-cso` run (`1795806574566-c85a630b4c8088a1`) reported `completeness: partial` from a static snapshot; it produced no findings and is **not** presented as an executed security test.

Scope limits:

- Sprint 0 / Phase 1 backend foundation only; no new school workflow was added.
- Frontend visibility is treated as presentation, not as an authorization boundary.
- No RBAC rewrite, second permission system, or unrelated domain change was made.
- MySQL was not available in the audit environment (`command -v mysql` exit 1), so MySQL-specific integration was not run. Tests used the project's SQLite test database.
- No remote CI, review approval, deployment, or merge result is asserted without command output.

## 2. Route inventory and authorization coverage matrix

Authoritative counts after remediation:

- **44 application-owned route registrations** from `php artisan route:list --except-vendor --json`.
- **76 expanded method–path bindings** (`GET|HEAD` and `PUT|PATCH` expanded by Laravel).
- **52 declared HTTP method choices** after excluding implicit `HEAD` and counting `PUT`/`PATCH` as declared choices.
- **39** app routes are behind `auth` + `school.context`; **2** are auth-only (`api/auth/logout`, `api/auth/me`); **3** are intentionally public (`/`, `/login`, `api/auth/login`).
- **36** app routes have an explicit `EnsurePermission` gate: 16 Academic Structure routes, 10 calendar routes, 3 role-assignment routes, 3 school-settings routes, 3 school-view routes, and 1 identifier route.
- **2** app routes use the tenant-signed storage guard instead of a permission gate; **1** remaining app route is membership-only and renders no school data (`app/modules/{module}`). No school-data route remains membership-only.

Middleware abbreviations: `W`=`web`, `A`=`auth`, `S`=`school.context` / `ResolveSchoolContext`, `P:x`=`EnsurePermission:x`, `T`=`EnsureTenantSignedStorage`. Rows 1–42 retain the original audit numbering; rows 43–44 are the replacement private-storage routes. The full machine-readable inventory is `/home/praisetechzw/b04-audit-evidence/route-inventory.tsv`.

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
| 37 | GET/HEAD | `app` | `App\Livewire\Dashboard` | Frontend | `W,A,S,P:school.view` | Data provider uses server context and checks permissions | `FrontendFoundationTest`, `B04RemainingGapsTest` | Guest redirect, role viewer dashboard allowed, provider payloads filtered. |
| 38 | GET/HEAD | `app/academic-calendar` | `Frontend\AcademicCalendarViewController@index` | Frontend | `W,A,S,P:academic_calendar.view` | Years/periods scoped to server context | `FrontendFoundationTest`, `CalendarAdministrationTest`, `B04RemainingGapsTest` | Fixed F04/F05. Guest/role_viewer 403; manage link hidden from members. |
| 39 | GET/HEAD | `app/academic-calendar/manage` | `App\Livewire\CalendarAdministration` | Frontend | `W,A,S,P:academic_calendar.manage` | Persistent school context; gateway scopes records | `CalendarAdministrationTest` | Fixed F01. Member 403, editor 200, unowned school selection 403, no foreign name in response. |
| 40 | GET/HEAD | `app/modules/{module}` | `Frontend\ModuleNoticeController@show` | Frontend | `W,A,S`; membership-only | No school data rendered | `FrontendFoundationTest` | Guest redirect/no-membership covered; no tenant test required. |
| 41 | GET/HEAD | `app/school` | `Frontend\SchoolProfileController@index` | Frontend | `W,A,S,P:school.view` | Identifiers scoped via permission-aware data provider | `FrontendFoundationTest`, `B04RemainingGapsTest` | Fixed F04. Cross-school no leak; identifiers hidden from role_viewer. |
| 42 | GET/HEAD | `login` | `Frontend\LoginController@show` | Frontend | `W,G` | No school context | `FrontendFoundationTest` | Public guest-only intended; authenticated redirect covered. |
| 43 | GET/HEAD | `storage/{path}` | `TenantFileController@show` | File storage | `W,A,S,T` | Signature binds `tenant_id` and canonical `schools/{id}/` path | `TenantSignedStorageTest` | Fixed F07. Signed read succeeds; unsigned/tampered/cross-school/dot-segment paths 403. |
| 44 | PUT | `storage/{path}` | `TenantFileController@store` | File storage | `W,A,S,T` | Upload flag bound to signature and HTTP method | `TenantSignedStorageTest` | Fixed F07. Signed upload succeeds; download signature cannot upload and vice versa. |

### Framework/vendor runtime routes (outside application route files)

`php artisan route:list --json` showed 50 registrations in total; the 6 additional entries are framework/package routes: Livewire JavaScript assets (2), `livewire/update`, `livewire/upload-file`, `livewire/preview-file/{filename}`, and health `/up`.

- `livewire/update` has only `web` at route level. The application now persists `auth`, `ResolveSchoolContext`, and `EnsurePermission` for Livewire updates, so the originating route's permission gate is replayed. Calendar gateway/domain writes also self-enforce `academic_calendar.manage`. F06 is closed for the current surface; every new Livewire write must still use the same explicit check.
- Laravel's unauthenticated framework signed-storage routes are disabled (`local.serve => false`). The 2 replacement routes are application-owned, authenticated, school-bound, and listed as rows 43–44 above.
- Livewire upload/preview and health/asset routes remain framework-owned. No application tenant-file usage of the Livewire upload/preview routes was found; `TenantFileStorage` is the supported path for private tenant files.

## 3. Permission and role matrix

### Configured permissions and effective enforcement

| Permission | Configured roles | Enforcement after B-04 | Status |
|---|---|---|---|
| `school.view` | `school_member`, `school_editor`, `role_viewer`, `school_admin` (`*`) | `/api/school`, `/app`, `/app/school`, and provider `school()` | Fixed F04 |
| `school_identifiers.view` | `school_member`, `school_editor`, `school_admin` (`*`) | `/api/school/identifiers` and provider `identifiers()` | Fixed F04 |
| `school_settings.view` | `school_member`, `school_editor`, `school_admin` (`*`) | `GET /api/school-settings`, `GET /api/school-settings/revisions` | Fixed F03 |
| `school_settings.manage` | `school_editor`, `school_admin` (`*`) | `PUT|PATCH /api/school-settings` | Fixed F03 |
| `academic_calendar.view` | `school_member`, `school_editor`, `school_admin` (`*`) | 5 calendar read routes (`validation`, `years`, `current`, `periods`, frontend calendar) and provider year/period reads | Fixed F04 |
| `academic_calendar.manage` | `school_editor`, `school_admin` (`*`) | 4 calendar API write routes, `/app/academic-calendar/manage`, and `DomainCalendarManagementGateway` writes | Fixed F01 |
| `academic_structure.view` | `school_member`, `school_editor`, `school_admin` (`*`) | 6 structure read routes | Existing, test coverage extended |
| `academic_structure.manage` | `school_editor`, `school_admin` (`*`) | 10 structure write routes | Existing, test coverage extended |
| `roles.view` | `role_viewer`, `school_admin` (`*`) | `GET api/role-assignments` | Existing |
| `roles.manage` | `school_admin` (`*`) | Role create/delete plus domain non-escalation checks | Existing |

`school_admin` uses the configured wildcard `*`; `SchoolPermissions` resolves only permissions present in the catalogue and requires an active assignment in the current school. Unknown roles/permissions fail closed. There is no direct user-permission table. `SchoolPermissions::authorize(User, School, permission)` is the fail-closed contract for callers outside route middleware; a regression proves it throws without the permission and succeeds after the role is granted.

### Documented role impact

This is a real access change and requires an owner/rollout decision before deployment:

- `school_member` now reads school settings, settings revisions, school identifiers, school context, and calendar records. Previously those endpoints were membership-only, so the visible surface is clearer but a roleless member no longer has implicit access.
- `school_editor` now has `school_settings.manage` in addition to its existing calendar and structure write permissions.
- `role_viewer` keeps the dashboard and role-assignment read, and still has `school.view`; it does **not** gain settings, identifier, or calendar reads. Sensitive provider payloads are empty/null for those actions.
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
- Private files use `schools/{school_id}/...` paths and signed URLs carrying a separate `tenant_id` parameter. `TenantFileStorage::assertTenantPath()` rejects prefixes outside the current school, empty/dot/dot-dot segments, backslashes, NUL bytes, and invalid characters. `EnsureTenantSignedStorage` requires authentication, an authorised school context, a valid signature, a matching signed tenant ID, canonical path validation, and an exact method/flag match (`upload=1` only for `PUT`).
- `config/filesystems.php` sets `local.serve => false`, so Laravel's unauthenticated framework signed-storage routes are not registered.

Executed tenant regressions include: cross-school calendar year/period 404, foreign offering update 404 with no leak, foreign class/year offering 422, unowned `X-School-Id` on the calendar management screen 403 without the foreign school name, role in another school grants no permission, empty school IDs do not widen access, roleless/member/role_viewer write denials, cross-school signed-storage replay 403, unsigned/tampered signature 403, download-signature upload misuse 403, upload-signature download misuse 403, path traversal signing failure, and middleware rejection of a signed dot-segment path.

## 5. Security findings

### F01 — High — fixed: calendar writes had no permission gate

Baseline route middleware on the 4 calendar write API routes was only `web,auth,school.context`. `academic_calendar.manage` existed in the catalogue but was never enforced. A roleless school member, `school_member`, or `role_viewer` could create/update academic years and periods and could use the frontend management screen and gateway.

**Fix:** reuse the existing architecture only — add `EnsurePermission:academic_calendar.manage` to the 4 write routes and the management screen, and add an internal `SchoolPermissions::has(...)` assertion in `DomainCalendarManagementGateway` for all gateway writes. No new permission, policy, gate, or domain rewrite was introduced.

**Regression evidence:** `AcademicCalendarPermissionTest` denies all 4 write routes for no role / `school_member` / `role_viewer`, allows `school_editor` / `school_admin`, and denies a role assigned only in another school. `CalendarAdministrationTest` denies the management screen, a Livewire write, and a gateway write.

### F02 — Medium — fixed: misleading `school_editor` label

The role label claimed calendar-only scope while the role also grants Academic Structure management. Changed to `School editor`; no permission or assignment change.

### F03 — Medium — fixed: school settings were membership-only

There was no settings permission in `config/authorization.php`, and all 3 settings routes relied on school membership alone. Tenant isolation was sound, but any school member could read settings/revisions and update settings.

**Fix:** add `school_settings.view` and `school_settings.manage` to the existing catalogue; grant view to `school_member`, `school_editor`, and admin wildcard, and manage to `school_editor` and admin wildcard. Apply `EnsurePermission` on the 3 routes and repeat the check in `SchoolSettingsController` using the server-resolved school. The role impact is documented in section 3.

**Regression evidence:** `B04RemainingGapsTest` proves member read/revisions 200, member write 403, editor write 200 and persisted to the correct school, and role_viewer read/write/revisions 403. Existing `SchoolSettingsIsolationTest` fixtures now carry the view/manage roles.

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

**Regression evidence:** `B04RemainingGapsTest` asserts both middleware are in `Livewire::getPersistentMiddleware()`; `CalendarAdministrationTest` continues to deny a Livewire calendar write without the manage permission.

### F07 — Low — fixed: signed framework file routes were not tenant-bound

Laravel's framework signed-storage routes were unauthenticated and bound only to a path. No application tenant-file usage was found, but the routes were an unsafe default for future private files.

**Fix:** disable framework `serve` routes on the private disk, add `TenantFileStorage`, `EnsureTenantSignedStorage`, and `TenantFileController`, and register authenticated `storage.local` / `storage.local.upload` routes behind `auth`, `school.context`, and the tenant-signature guard. The signature binds a `tenant_id` separate from the client's `school_id`, and the path must start with the current school's canonical directory.

**Regression evidence:** `TenantSignedStorageTest` covers signed read/upload success, cross-school replay, unsigned and tampered URLs, download/upload flag misuse, traversal at signing time, dot segments at middleware time, and route middleware membership.

### F08 — Informational — no cross-school IDOR confirmed

Tested offering-by-ID update, foreign offering class/year references, foreign calendar IDs, foreign school selection, cross-school role assignment, frontend unowned selection, and signed-storage replay/tampering. All failed closed without foreign data in responses. The previously untested offering `school_id` prohibition is now explicitly covered for create and update.

### F09 — Informational — architecture boundary preserved

No Gate/Policy definitions exist; authorization remains the existing middleware + `SchoolPermissions` + domain-guard architecture. No RBAC rewrite, duplicate permission system, or unrelated school workflow was added.

## 6. Tests added or changed

B-04 adds **42 tests / 157 assertions** relative to the pre-B-04 tree. The final branch reports **286 passed / 1164 assertions**; the original audited `main` reported 243 / 990 and current `main` reported 244 / 1007 before B-04 changes.

| File | Added regression coverage |
|---|---|
| `tests/Feature/Domains/AcademicCalendar/AcademicCalendarPermissionTest.php` (new) | Calendar writes denied for no role / `school_member` / `role_viewer`; allowed for `school_editor` / `school_admin`; cross-school role grants nothing. |
| `tests/Feature/Domains/AcademicCalendar/AcademicCalendarIsolationTest.php` | Calendar year/period updates reject client `school_id`; null `school_id` cannot widen ownership; fixtures now carry a calendar-capable role. |
| `tests/Feature/Domains/AcademicStructure/AcademicStructureIsolationTest.php` | Offering create/update `school_id` rejection; foreign school context on update 403; foreign offering update 404 without leak; foreign class/year IDs generic 422; null `school_id` ignored. |
| `tests/Feature/Domains/AcademicStructure/AcademicStructurePermissionTest.php` | Nested grade/class/stream writes and offering create/update denied for `school_member`, allowed for `school_editor`. |
| `tests/Feature/Domains/Authorization/B04RemainingGapsTest.php` (new) | Settings view/manage denials and grants; read-gate denials on school, identifiers, validation, years, current, periods, and calendar screen; role_viewer payload filtering; manage-link visibility; Livewire persistent middleware; `SchoolPermissions::authorize()` fail-closed contract. |
| `tests/Feature/Domains/Authorization/TenantSignedStorageTest.php` (new) | Signed private read/upload success; cross-school replay; unsigned/tampered tenant ID; download/upload flag misuse; path traversal and dot segments; route middleware contract. |
| `tests/Feature/Domains/School/SchoolIsolationTest.php` | Empty header/query school IDs fail closed without widening access; fixtures carry the new read permissions. |
| `tests/Feature/Domains/SchoolSettings/SchoolSettingsIsolationTest.php` | Existing isolation suite now grants settings roles so the permission gate does not mask tenant assertions. |
| `tests/Feature/Domains/Auth/AuthenticationTest.php`, `tests/Feature/Domains/Authorization/RolesPermissionsTest.php`, `tests/Feature/Frontend/FrontendFoundationTest.php`, `tests/Pest.php` | Fixtures use the shared `grantSchoolRole()` helper and now carry the permissions required by gated reads. |
| `tests/Feature/Frontend/CalendarAdministrationTest.php` | Management screen 403 for non-editor; Livewire write denial; gateway permission enforcement; unowned school selection on management route 403 with no foreign school name. |
| `tests/Feature/Domains/AuditTrail/AuditTrailIsolationTest.php` | Existing audit fixtures now use `school_editor` because calendar writes require `academic_calendar.manage`. |

## 7. Verification evidence

Commands were executed locally in the isolated worktree. Pre-restart runs used `/tmp/opencode/b04-authorization-audit`; final post-restart verification used `/home/praisetechzw/.local/share/opencode/worktree/b04-authorization-audit-recovered`. Exit codes are from the final post-fix state unless marked baseline.

| Command | Result | Evidence |
|---|---|---|
| `make COMPOSE="docker compose -p b04-audit" verify` at baseline `c21d0a9` | Exit 0; Pint 207 files; PHPStan `[OK] No errors`; Composer audit clean; **243 passed / 990 assertions** | `/tmp/opencode/b04-baseline-verify.log` |
| Focused security suites before rebase (`docker compose -p b04-audit exec -T app php artisan test …`) | Exit 0; **163 passed / 654 assertions** | Original log was removed by the environment restart; result is retained in this audit session. |
| `make COMPOSE="docker compose -p b04-audit" verify` before rebase | Exit 0; Pint 208 files; PHPStan `[OK] No errors`; Composer audit clean; **263 passed / 1088 assertions** | Original log was removed by the environment restart; result is retained in this audit session. |
| `make COMPOSE="docker compose -p b04-audit-r -f …" verify` after rebase, before the F03–F07 phase | Exit 0; Pint 208 files; PHPStan `[OK] No errors`; Composer audit clean; **264 passed / 1105 assertions** | `/home/praisetechzw/b04-audit-evidence/make-verify.log` |
| Focused security suites after rebase, inside `b04-audit-r` | Exit 0; **164 passed / 671 assertions** | `/home/praisetechzw/b04-audit-evidence/focused-tests.log` |
| New F03–F07 regressions (`B04RemainingGapsTest`, `TenantSignedStorageTest`) | Exit 0; **22 passed / 59 assertions** | `/home/praisetechzw/b04-audit-evidence/new-regressions.log` |
| Final focused security suites | Exit 0; **166 passed / 755 assertions** | `/home/praisetechzw/b04-audit-evidence/focused-tests-final.log` |
| Final `make COMPOSE="docker compose -p b04-audit-r -f …" verify` | Exit 0; Pint 213 files; PHPStan `[OK] No errors`; Composer audit `No security vulnerability advisories found`; **286 passed / 1164 assertions** | `/home/praisetechzw/b04-audit-evidence/make-verify-final.log` |
| `docker compose -p b04-audit-r … exec -T app php artisan route:list --except-vendor --json` after final changes | 44 app routes; 76 method tokens; 52 declared method choices; 36 permission gates; 2 tenant-storage guards | `/home/praisetechzw/b04-audit-evidence/route-list-after.json`, `/home/praisetechzw/b04-audit-evidence/route-inventory.tsv` |
| `docker compose -p b04-audit-r … exec -T app php artisan route:list --json` after final changes | 50 total registrations; 6 framework/package routes; no framework `storage/{path}` routes | `/home/praisetechzw/b04-audit-evidence/route-list-all-after.json` |
| `docker compose -p b04-audit-r -f … config -q` | Exit 0 | Command output captured in this audit session; override at `/home/praisetechzw/b04-audit-evidence/compose-override.yml` |
| `git diff --check` | Exit 0 | Command output captured in this audit session |
| `git ls-remote origin refs/heads/main` | Initially `c21d0a9…`; final observation `6844b4289af6c5446b80d897f73de39d4bb6aa7b` | Command output captured in this audit session |
| `command -v mysql` | Not available; MySQL integration not run | Command output captured in this audit session |
| `gstack-cso` static run `1795806574566-c85a630b4c8088a1` | `completeness: partial`; no findings; static snapshot only, not an executed security test | Tool run record |

No GitHub Actions check-run result, review approval, deployment, or merge is claimed for B-04 in this document.

## 8. Risks, owner actions and status

| ID | Risk | Likelihood / impact | Status | Required owner action |
|---|---|---|---|---|
| F03 | Any school member could read/update school settings | Medium / high | **Fixed and regression-tested** | Confirm the documented `school_member` read / `school_editor` write grants before rollout. |
| F04 | Catalogued read permissions were not enforced; identifier visibility was undecided | Medium / medium | **Fixed and regression-tested** | Confirm identifier visibility for members and the roleless-member denial; assign roles to existing users before rollout. |
| F05 | Manage link visible to users who receive 403 | High / low | **Fixed and regression-tested** | No action required beyond review. |
| F06 | Future Livewire actions could bypass route permission gates | Medium / high | **Fixed for current surface** | Keep using gateway/domain authorization for every new Livewire write. |
| F07 | Future tenant files could be exposed via signed routes | Low / high if introduced | **Fixed for application-owned storage** | Use `TenantFileStorage` for private files; do not expose Livewire vendor upload/preview for tenant files without a separate tenant-binding review. |
| R-01 | Roleless school members lose previously implicit read access | Medium / medium | **Open rollout action** | Assign `school_member` (or the appropriate configured role) to existing memberships before deployment. No schema migration needed. |
| F01 | Roleless calendar writes | High / high | **Fixed and regression-tested** | Human review of role impact; no migration required. |
| F02 | Misleading role label | Medium / low | **Fixed** | Confirm display text in UI copy if referenced. |

## 9. Integration tracker and traceability

- `docs/github/integration-tracker.md` was stale at `28079f5…`; it is reconciled to the observed current `origin/main` `6844b4289af6c5446b80d897f73de39d4bb6aa7b`, including PRs #59–#61.
- Audited baseline: `c21d0a90530cc2764853e6b3eabb277501da696b`.
- Current remote baseline at report time: `6844b4289af6c5446b80d897f73de39d4bb6aa7b`.
- Change branch: `audit/b04-authorization-tenant-isolation` (rebased onto `6844b42`).
- Review PR: [#62](https://github.com/PraiseTechzw/zimbabwe-school-management-system/pull/62) — opened, not merged; no remote check result is claimed here.
- Route evidence: `/home/praisetechzw/b04-audit-evidence/route-list-after.json`, `/home/praisetechzw/b04-audit-evidence/route-inventory.tsv`, and `/home/praisetechzw/b04-audit-evidence/route-list-all-after.json`.
- Verification evidence: `/home/praisetechzw/b04-audit-evidence/make-verify-final.log`, `/home/praisetechzw/b04-audit-evidence/focused-tests-final.log`, `/home/praisetechzw/b04-audit-evidence/new-regressions.log`, `/home/praisetechzw/b04-audit-evidence/compose-override.yml`, and `/home/praisetechzw/b04-audit-evidence/final-verify.log` (host-equivalent rerun). Earlier `/tmp/opencode/b04-*` artifacts were removed by an environment restart; their results are preserved in this report.
- This report is the authoritative B-04 route/permission matrix, role-impact record, and risk register.

### Handoff to Audit Coverage agent

Please verify that the 44-route matrix, the fixed F03–F07 gates, the explicit role grants and rollout action R-01, and the risk register match your coverage record. Do not merge PR #62 before review and remote check evidence exist. Do not report B-04 CI or review status until remote command output exists.
