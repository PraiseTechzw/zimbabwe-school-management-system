# Frontend progress tracker

Updated: 2026-11-27. Status vocabulary: `NOT STARTED`, `UI IN PROGRESS`, `UI COMPLETE`, `MOCK INTEGRATION`, `API INTEGRATION IN PROGRESS`, `API INTEGRATED`, `TESTED`, `BLOCKED`.

Gate: ADR-0003 accepted 2026-11-27 (Blade + Livewire + Alpine + Tailwind). Module functionality still requires its own approved specification; UI scaffolding here is not a claim that a school workflow is implemented.

## Stage A — foundation

| Deliverable | Status | Screens / components | API dependencies | Mock data | Tests | Known issues / next task |
| --- | --- | --- | --- | --- | --- | --- |
| ADR-0003 acceptance + design source of truth | TESTED | `ADR-0003`, `PRODUCT.md`, `DESIGN.md` | None | None | N/A | Prototype payload measurement remains a condition of ADR-0003. |
| Design tokens + reusable component set | UI IN PROGRESS | Pending | None | None | Pending | Implement in next task group. |
| Application shell (sidebar, mobile sheet, header, breadcrumbs, title row) | UI IN PROGRESS | Pending | `GET /api/auth/me`, `GET /api/school` | None | Pending | School switcher is an interface only until a multi-school selection endpoint exists. |
| Sign-in screen | UI IN PROGRESS | Pending | `POST /api/auth/login` | None | Pending | Measure payload size once built. |
| Dashboard framework | UI IN PROGRESS | Pending | `GET /api/auth/me`, `GET /api/school`, `GET /api/academic-years/current` | Clearly labelled demo values for unimplemented modules | Pending | Replace demo adapter per module when endpoints exist. |
| API client + mock adapter | UI IN PROGRESS | Pending | Existing JSON endpoints listed in `api-dependencies.md` | Isolated mock adapter | Pending | No invented endpoint contracts. |
| Frontend tests + local docs | UI IN PROGRESS | Pending | None | None | Pending | Record commands and exit codes here. |

## Stage B–O — school modules

All modules are `NOT STARTED` unless listed below. Building a navigation entry does not mean the module is available.

| Module | Status | Screens | API dependencies | Integration | Tests | Next task |
| --- | --- | --- | --- | --- | --- | --- |
| School identity / profile | NOT STARTED | — | `GET /api/school`, `GET /api/school/identifiers` | Real endpoint read-only UI planned | — | Stage B |
| Academic calendar | NOT STARTED | — | `GET /api/academic-years`, `GET /api/academic-years/current`, `GET /api/academic-years/{id}/periods` | Real endpoints available | — | Stage B |
| Admissions, learners, guardians, enrolment, academics, attendance, finance, staff, welfare, boarding, transport, operations, library, activities, communication, governance, parent/learner portals | NOT STARTED | — | Not available | None | — | Requires approved module specification and endpoint contract |

## Prototype evidence (ADR-0003 condition)

To be completed after the first read-heavy and form-heavy screens ship:

- Read-heavy screen: dashboard shell — record Vite CSS/JS gzip sizes and the number of network requests for one page load.
- Form-heavy screen: sign-in — record the same, plus observed interaction behaviour.
- No remote CI result is claimed here; only commands run locally with output are recorded.

## Verification log

| Date | Command | Exit code | Evidence |
| --- | --- | --- | --- |
| 2026-11-27 | `make verify` | 0 | 96 tests, 352 assertions passed (pre-frontend foundation state) |
| 2026-11-27 | `docker compose config -q` | 0 | No output |
| 2026-11-27 | `npm run build` | 0 | 3 modules; CSS 55.21 kB / gzip 11.73 kB; JS 0.00 kB / gzip 0.02 kB |
