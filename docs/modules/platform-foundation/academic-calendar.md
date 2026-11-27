# Academic Calendar foundation

**Status:** implemented backend foundation for issue #39.
**Evidence basis:** research topic 7 in [../../research/findings/03-calendar-and-access.md](../../research/findings/03-calendar-and-access.md), workflow WF-02 in [../../research/proposed-workflows.md](../../research/proposed-workflows.md) and the phase decisions in [decision-log.md](decision-log.md).
**Not included:** authentication, roles/permissions, examination windows, audit revisions or a user interface.

## Domain overview

A school records its own academic years. Each academic year can hold any number of dated periods. A period is a generic calendar subdivision; the model does not assume three terms, fixed names, a fixed count or a universal Zimbabwean calendar.

Records are school-owned and scoped by the server-side `SchoolContext` established from the authenticated user's memberships. Client-supplied school identifiers can only select among those memberships and never create or widen access.

### Stored fields

| Entity | Fields |
| --- | --- |
| `academic_years` | `school_id`, `name`, `start_date`, `end_date`, `status`, `source`, `source_reference`, `source_published_at`, timestamps |
| `academic_periods` | `school_id`, `academic_year_id`, `name`, `type`, `sequence`, `start_date`, `end_date`, `status`, `counting_basis`, `source`, `source_reference`, `source_published_at`, timestamps |

`source` is required on every write. `source_reference` and `source_published_at` are optional because the evidence supports recording authoritative provenance but does not define a single required reference format.

## Rules enforced on writes

- Dates use `Y-m-d`; the end date must be after the start date.
- Academic year names are unique within a school. They may repeat across schools.
- Period names and period sequences are unique within an academic year. They may repeat across years.
- A period must fall inside its academic year by default (`academic-calendar.require_periods_within_year`).
- Periods cannot overlap by default (`academic-calendar.prevent_period_overlap`). Date ranges are inclusive, so a period ending on 31 March and one starting on 1 April are adjacent; a period starting on 31 March overlaps.
- Academic year overlap is disabled by default (`academic-calendar.prevent_year_overlap`). It is an engineering safeguard, not an evidence-backed requirement, so it can be enabled by configuration.
- Statuses, period types and counting bases are read from `config/academic-calendar.php`. Unknown values are rejected.
- New records default to `draft`. The configured values do not prove that every school uses the same publication workflow.
- `counting_basis` exists because source day counts conflict (C-07: school days versus calendar days).
- Integrity checks and writes run in a transaction with row locks; database unique constraints remain the final guard.

`ValidateAcademicCalendar` reports stored-data problems without mutating records. The validation endpoint returns `valid` plus a list of human-readable issues.

## Provisional rules and exclusions

- Period type options (`term`, `semester`, `break`, `other`) are generic engineering options. Only documented term practice is evidenced; the other options are not a claim about Zimbabwean schools.
- Status options and the integrity switches are configurable. They are provisional until a Product Owner or source-backed decision confirms them.
- Review before publication and auditable revisions from WF-02 are deferred to the permissions and audit-trail slices. Status changes are metadata only in this slice.
- Examination windows are a separate domain (PF-031) and are not implemented here.
- No numeric offline or resilience targets were inferred.
- No stakeholder-interview validation is claimed.

## Interfaces

All endpoints sit behind `auth` and `school.context` in `routes/web.php`.

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/api/academic-years` | List the current school's academic years by start date and ID |
| `POST` | `/api/academic-years` | Create an academic year |
| `PUT\|PATCH` | `/api/academic-years/{academicYear}` | Update an academic year in the current school |
| `GET` | `/api/academic-years/{academicYear}/periods` | List one year's periods by sequence and ID |
| `POST` | `/api/academic-years/{academicYear}/periods` | Create a period in the route's year |
| `PUT\|PATCH` | `/api/academic-periods/{academicPeriod}` | Update a period in the current school |
| `GET` | `/api/academic-calendar/validation?academic_year_id=...` | Report validation issues for one scoped year |

Domain actions live under `app/Domains/AcademicCalendar/Actions/`: `CreateAcademicYear`, `UpdateAcademicYear`, `CreateAcademicPeriod`, `UpdateAcademicPeriod` and `ValidateAcademicCalendar`. Controllers validate requests, resolve the school context and delegate to those actions.

The route identifier is authoritative for period creation: a client-supplied `academic_year_id` in the body cannot redirect a period to another year. `school_id` is rejected as client input on calendar writes and validation requests.

Tenant-varying JSON responses send `Cache-Control: no-store` and `Vary: X-School-Id`.

## Isolation behaviour

- `AcademicYear` and `AcademicPeriod` opt into `ScopesToCurrentSchool`; there is no global scope on school-owned models.
- Reads use `forCurrentSchool()` and fail closed when no school has been authorised.
- Cross-school year or period IDs resolve to `404` over HTTP. In domain actions they raise `ValidationException` for an invalid year reference or `AuthorizationException` for an update outside the school.
- An unauthorised client-selected school ID is rejected with `403` by the existing context middleware; conflicting or malformed IDs are rejected with `400`.
- A missing context never falls back to a client value or to another tenant's data.
- Permissions are not part of this slice. Any authenticated user with school membership can use the endpoints until the roles/permissions slices are implemented.

## Implementation decisions

| ID | Decision | Reason |
| --- | --- | --- |
| AC-D-01 | Require a source on create and update; keep reference and publication date optional. | WF-02 records authoritative provenance to reduce risk from unofficial calendars; no single reference format was evidenced. |
| AC-D-02 | Enable period boundaries and overlap protection by default; leave year overlap configurable and off. | Boundaries and period ordering are engineering integrity needs; year overlap is not a verified requirement. |
| AC-D-03 | Keep statuses, period types and counting basis in configuration. | D-02 prohibits treating unverified formats or structures as confirmed requirements. |
| AC-D-04 | Do not implement exam windows or revision history. | PF-031 is separate; audit trail and permissions are later slices. |
| AC-D-05 | Use the provisional shared-database boundary from D-04 with explicit server-side scoping. | Evidence does not mandate a tenancy topology; the boundary remains reversible. |
