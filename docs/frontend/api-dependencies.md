# Frontend API dependency tracker

Updated: 2026-11-27. This list covers Stage B frontend consumers. Only endpoints verified by reading this repository are listed. Provisional contracts are labelled and must not be treated as backend promises.

Stage B uses server-side adapters that call the same domain actions as the JSON controllers. “Integrated in-process” below means the UI has a verified adapter in this branch; it does **not** claim an extra browser HTTP request.

## Verified endpoints

| Endpoint | Method | Auth / tenancy | Response shape (verified in controller) | UI consumer | Integration status |
| --- | --- | --- | --- | --- | --- |
| `/api/auth/login` | POST | `throttle:login`, `throttle:login-ip`; body `{email, password}` | `{user: {id, name, email, status, email_verified_at, last_login_at}}` | Sign-in form | Available — integrated by sign-in form |
| `/api/auth/logout` | POST | `auth`; CSRF (web middleware) | `{message: "Logged out."}` | User menu | Available — integrated by user menu |
| `/api/auth/me` | GET | `auth` | `{user: {id, name, email, status, email_verified_at, last_login_at}}` | Shell user row, dashboard | Available — payload mirrored by the server adapter; no HTTP call |
| `/api/school` | GET | `auth`, `school.context` | `{school: {id, name, type}}`; `Cache-Control: no-store`, `Vary: X-School-Id` | School context strip | Available — integrated in server adapter |
| `/api/school/identifiers` | GET | `auth`, `school.context` | `{school_id, identifiers: [{id, school_id, type, value, verification_status}]}` | School identity view | Available — integrated in server adapter |
| `/api/academic-years/current` | GET | `auth`, `school.context` | `{school_id, academic_year: null \| {id, school_id, name, start_date, end_date, status, source, source_reference, source_published_at}}` | Dashboard current-period panel | Available — integrated in server adapter |
| `/api/academic-years` | GET | same | `{school_id, academic_years: [year payload]}` | Academic calendar list + administration | Available — integrated in server adapter |
| `/api/academic-years/{id}/periods` | GET | same | `{school_id, academic_year_id, periods: [period payload]}` | Academic calendar detail + administration | Available — integrated in server adapter |
| `/api/academic-calendar/validation` | GET | same; requires `academic_year_id` | `{school_id, academic_year_id, valid, issues}` | Calendar validation panel | Available — integrated in-process by `DomainCalendarManagementGateway` |
| `/api/academic-years` | POST | `auth`, `school.context`; FormRequest rejects `school_id` | `{academic_year: year payload}`, HTTP 201 | New academic year form | Available — integrated in-process via `CreateAcademicYear` |
| `/api/academic-years/{academicYear}` | PUT, PATCH | same; route ID must belong to the current school | `{academic_year: year payload}` | Edit academic year form | Available — integrated in-process via `UpdateAcademicYear` |
| `/api/academic-years/{academicYear}/periods` | POST | same; route ID must belong to the current school | `{period: period payload}`, HTTP 201 | New period form | Available — integrated in-process via `CreateAcademicPeriod` |
| `/api/academic-periods/{academicPeriod}` | PUT, PATCH | same; route ID must belong to the current school | `{period: period payload}` | Edit period form | Available — integrated in-process via `UpdateAcademicPeriod` |
| `/api/school-settings` | GET | `web`, `auth`, `school.context`; `school_id` prohibited | `{school_id, settings, revision: {version, source, effective_from}}`; `Cache-Control: no-store`, `Vary: X-School-Id, school_id` | Planned School settings view | Available — contract verified; no UI built |
| `/api/school-settings` | PUT, PATCH | same; validation from `ValidateSchoolSettings` rejects secret-like keys | `{school_id, settings, revision: {version, source, effective_from}}` | Planned School settings form | Available — contract verified; no UI built |
| `/api/school-settings/revisions` | GET | `web`, `auth`, `school.context`; `school_id` prohibited | `{school_id, revisions: [revision payload]}`; `Cache-Control: no-store`, `Vary: X-School-Id, school_id` | Planned revision history | Available — contract verified; no UI built |
| `/api/role-assignments` | GET | `web`, `auth`, `school.context`, `roles.view`; `school_id` prohibited | `{school_id, role_assignments: [assignment payload]}`; `Cache-Control: no-store`, `Vary: X-School-Id, school_id` | Planned roles view | Available — contract verified; no UI built |
| `/api/role-assignments` | POST | `web`, `auth`, `school.context`, `roles.manage`; body `{user_id, role}`; `school_id` prohibited | `{role_assignment: assignment payload}`, HTTP 201 | Planned role assignment form | Available — contract verified; no UI built |
| `/api/role-assignments/{roleAssignment}` | DELETE | `web`, `auth`, `school.context`, `roles.manage`; numeric route ID scoped to current school | HTTP 204, no body; `Cache-Control: no-store`, `Vary: X-School-Id, school_id` | Planned role assignment removal | Available — contract verified; no UI built |

### Academic Structure contracts (verified, no frontend UI)

Field-level request and response contracts, status codes and validation rules are recorded in the [Academic Structure API](../api/academic-structure.md). This section records the frontend readiness view of the same verified surface.

`app/Providers/AcademicStructureServiceProvider.php` registers `routes/academic-structure.php` under `web`, `auth` and `school.context` with the `/api` prefix. Reads require `academic_structure.view`; writes require `academic_structure.manage`. Tenant-varying responses send `Cache-Control: no-store` and `Vary: X-School-Id, school_id`. `php artisan route:list --path=api/academic-structure --json` returned 16 route objects on this base.

| Resource | Verified routes | Verified envelope | Status |
| --- | --- | --- | --- |
| Educational levels | `GET`, `POST /api/academic-structure/levels`; `PUT\|PATCH /api/academic-structure/levels/{educationalLevel}` | Lists: `{school_id, educational_levels: [...]}`; singular: `{educational_level}` | Available — contract verified; no UI built |
| Grade levels | `GET /api/academic-structure/grade-levels`; `GET`, `POST /api/academic-structure/levels/{educationalLevel}/grade-levels`; `PUT\|PATCH /api/academic-structure/grade-levels/{gradeLevel}` | Lists: `{school_id, grade_levels: [...]}`; nested list adds the parent ID; singular: `{grade_level}` | Available — contract verified; no UI built |
| Class definitions | `GET`, `POST /api/academic-structure/grade-levels/{gradeLevel}/classes`; `PUT\|PATCH /api/academic-structure/classes/{classDefinition}` | Lists: `{school_id, classes: [...]}`; nested list adds the parent ID; singular key is `{class}` (not `class_definition`) | Available — contract verified; no UI built |
| Streams | `GET`, `POST /api/academic-structure/classes/{classDefinition}/streams`; `PUT\|PATCH /api/academic-structure/streams/{stream}` | Lists: `{school_id, streams: [...]}`; nested list adds the parent ID; singular: `{stream}` | Available — contract verified; no UI built |
| Class offerings | `GET`, `POST /api/academic-structure/class-offerings` (list accepts an owned `academic_year_id` query filter); `PUT\|PATCH /api/academic-structure/class-offerings/{classOffering}` | Lists: `{school_id, class_offerings: [...]}`; singular: `{class_offering}` | Available — contract verified; no UI built |

Integration constraints verified from the controller, FormRequests, domain actions and `docs/modules/academic-structure.md`:

- Creates take parent IDs from the route; updates accept owned parent IDs and resolve them through the current school. Client `school_id` is prohibited on writes and on the offerings list query.
- There is no seeded stage, grade, class or stream model, no pagination, no `is_active` filter and no delete/archive endpoint. Do not infer a national structure or stream semantic in the UI.
- Writes permit the documented fields only: `name`, optional `code`, `display_order`, optional `is_active`, optional `configuration`, optional offering `name`, and owned parent IDs. Bounds and active-parent rules come from `config/academic-structure.php`.
- Backend permissions remain authoritative. There is still no verified frontend permission map, so UI visibility must not be inferred from membership or these route rules.

## Stage B integration notes

- `app/Frontend/Contracts/CalendarManagementGateway.php` is the frontend write boundary. `DomainCalendarManagementGateway` derives the school exclusively from `SchoolContext` and calls the same create, update and validation actions used by `AcademicCalendarController`.
- Livewire updates do not replay the original page route. `ResolveSchoolContext` is registered as persistent Livewire middleware; for an update request the authorised school ID is read from the session and rechecked against the authenticated user's current memberships. A stale or forged selection cannot widen access; a removed membership fails closed instead of falling back to another school.
- The selected academic year ID is authoritative for period writes, matching the API route ID. The adapter overwrites any client-supplied `academic_year_id` with that selection.
- Write FormRequests require `source` and `Y-m-d` dates. Status, period type and counting-basis values come from `config/academic-calendar.php`, not from hard-coded school-structure assumptions.
- Domain actions enforce school scoping, name/sequence uniqueness, period-within-year containment, overlap rules and year-shrink containment under transaction and school-row locking. The UI displays returned errors but does not duplicate or replace that authorization.
- Field errors are surfaced inline; general failures and success states use the shared status primitives. Stage B introduces no demo or mock records.

## Rules for integration

- Never send a client-supplied `school_id`: the backend rejects it (`prohibited`) on calendar endpoints and resolves tenancy server-side.
- Use the session cookie and CSRF token from the Laravel page; do not store credentials in browser storage.
- Treat every payload field as untrusted input: escape on output in Blade, never inject raw HTML.
- A missing endpoint means a clearly labelled mock adapter, not an invented URL. Add provisional contracts here with `PROVISIONAL` in the status before writing UI against them.
- Keep fetch/request counts low: one request per block, no polling on the dashboard unless a real need is documented.
- UI visibility is not authorization. Role-assignment and Academic Structure endpoints carry backend `roles.view` / `roles.manage` and `academic_structure.view` / `academic_structure.manage` requirements, but there is still no verified frontend permission map; never hide or show an action on the assumption that it mirrors backend permissions.

## Known gaps

- No endpoint for school switching, notifications, dashboards aggregates, or a module availability catalogue. Navigation uses local configuration and honest "not available yet" states.
- No delete/archive calendar endpoints were verified; the management screen does not offer those actions.
- School Settings, Role Assignment and Academic Structure HTTP contracts are verified on this base but have no frontend UI in Stage B. Audit has no HTTP read endpoint. Contracts from unmerged backend work remain unavailable until merged and verified here.
- No frontend permission map yet; backend authorization remains authoritative.
