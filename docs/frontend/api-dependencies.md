# Frontend API dependency tracker

Updated: 2026-11-27. Only endpoints verified by reading this repository are listed. Provisional contracts are labelled and must not be treated as backend promises.

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
- UI visibility is not authorization. Role-assignment endpoints carry backend `roles.view` and `roles.manage` requirements, but there is still no verified frontend permission map; never hide or show an action on the assumption that it mirrors backend permissions.

## Known gaps

- No endpoint for school switching, notifications, dashboards aggregates, or a module availability catalogue. Navigation uses local configuration and honest "not available yet" states.
- No delete/archive calendar endpoints were verified; the management screen does not offer those actions.
- School Settings and Role Assignment HTTP contracts are verified on this base but have no frontend UI in Stage B; Academic Structure and Audit endpoints remain absent from this branch. Contracts found in unmerged backend work are not treated as available here.
- No frontend permission map yet; backend authorization remains authoritative.
