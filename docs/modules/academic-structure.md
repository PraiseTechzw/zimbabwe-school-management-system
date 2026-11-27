# Academic Structure foundation

**Status:** implemented backend foundation, not deployed and not user-facing. Rules marked **PROPOSED** are either research candidates awaiting confirmation or provisional engineering safeguards; they are not approved product requirements.
**Evidence basis:** [Findings 02 — school structures, stages and organisational units](../../research/findings/02-organisational-structures.md) (source IDs `STR-*`), [module specification template](../../templates/module-specification.md).
**Not included:** authentication, roles/permissions, learner/student records, enrolment, attendance, fees, assessment, reporting, HR, boarding or a user interface.

## Purpose and non-goals

A school defines its own structural hierarchy: educational level → grade level → class definition → optional stream. A class definition may be offered in one of the school's academic years (`class_offerings`). Offerings reference class definitions, not streams. The module stores structure only; a class definition holds no learners and an offering holds no enrolment or progression.

- **EVIDENCE (STR-05, indexed excerpt):** MoPSE statistical nomenclature includes ECD A, ECD B, Grades 1–7, Forms 1–4, Lower 6 and Upper 6. These names are examples for configuration only. The module seeds no levels, grades, classes or streams and makes no claim about a universal national model.
- **No universal assumptions:** no prescribed stage count, no Grade ↔ Form crosswalk, no fixed class size, no mapping of a stream to a subject pathway or a parallel group.
- **No school functionality beyond structure:** no admissions, attendance, fees, learner records, assessment, reporting, HR or boarding.
- **No authentication work:** endpoints rely on the existing `auth` + `school.context` middleware; no new login, role or permission model is introduced.
- **API-only bootstrap:** there is no seeder, CLI command or UI for creating the initial structure. Schools must use the JSON endpoints.
- **No delete surface:** cascade rules exist in the database, but this slice exposes no delete/archive endpoint.

## Data ownership and model

All five structure tables are owned by this context. Other contexts must not write them directly; this boundary is a modular convention, not yet enforced by architecture tests. `academic_years` remains owned by Academic Calendar; this module only adds a school-scoped candidate key to it.

| Table | Fields |
| --- | --- |
| `educational_levels` | `id`, `school_id` (FK `schools`, cascade), `name`, `code` nullable, `display_order` (unsigned integer), `is_active` (boolean, default `true`), `configuration` (JSON, nullable), timestamps |
| `grade_levels` | `id`, `school_id` (FK `schools`, cascade), `educational_level_id`, `name`, `code` nullable, `display_order`, `is_active` (default `true`), timestamps |
| `class_definitions` | `id`, `school_id` (FK `schools`, cascade), `grade_level_id`, `name`, `code` nullable, `display_order`, `is_active` (default `true`), `configuration` (JSON, nullable), timestamps |
| `streams` | `id`, `school_id` (FK `schools`, cascade), `class_definition_id`, `name`, `code` nullable, `display_order`, `is_active` (default `true`), timestamps |
| `class_offerings` | `id`, `school_id` (FK `schools`, cascade), `class_definition_id`, `academic_year_id`, `name` nullable, `is_active` (default `true`), `configuration` (JSON, nullable), timestamps |

Parent links use **composite foreign keys** that include `school_id` and reference `(school_id, id)` on the parent:

- `grade_levels(school_id, educational_level_id)` → `educational_levels(school_id, id)`.
- `class_definitions(school_id, grade_level_id)` → `grade_levels(school_id, id)`.
- `streams(school_id, class_definition_id)` → `class_definitions(school_id, id)`.
- `class_offerings(school_id, class_definition_id)` → `class_definitions(school_id, id)`.
- `class_offerings(school_id, academic_year_id)` → `academic_years(school_id, id)`.

The last link is enabled by an additive migration that creates the unique key `academic_years_school_id_id_uq` on `(school_id, id)`; it changes no calendar data. All listed foreign keys use `cascadeOnDelete()`, so deleting a school or an ancestor removes its descendants. Deleting an educational level, for example, removes its grades, their classes, streams and offerings.

Unique constraints and indexes:

- `educational_levels`: `(school_id, name)`, `(school_id, code)`, `(school_id, display_order)`, `(school_id, id)`; index `(school_id, is_active, display_order)`.
- `grade_levels`: `(school_id, name)`, `(school_id, code)`, `(school_id, educational_level_id, display_order)`, `(school_id, id)`; index `(school_id, educational_level_id, is_active, display_order)`.
- `class_definitions`: `(school_id, grade_level_id, name)`, `(school_id, grade_level_id, code)`, `(school_id, grade_level_id, display_order)`, `(school_id, id)`; index `(school_id, grade_level_id, is_active, display_order)`.
- `streams`: `(school_id, class_definition_id, name)`, `(school_id, class_definition_id, code)`, `(school_id, class_definition_id, display_order)`, `(school_id, id)`; index `(school_id, class_definition_id, is_active, display_order)`.
- `class_offerings`: `(school_id, academic_year_id, class_definition_id)` named **`class_offerings_school_year_class_unique`**, `(school_id, academic_year_id, name)` named **`class_offerings_school_year_name_unique`**, and `(school_id, id)`; index `(school_id, academic_year_id, is_active)`.

SQL unique constraints treat `NULL` `code`/`name` values as non-duplicates; the application adds case-insensitive checks for supplied labels. Those checks use `LOWER(...)`/`Str::lower()`, which can diverge for non-ASCII data on MySQL; ADR-0002 flags untested driver differences. Exact-case database constraints remain the final guard.

## Rules enforced on writes

Shared rules live in `app/Domains/AcademicStructure/Support/AcademicStructureRules.php`; all ten `Create*`/`Update*` actions validate again before writing, so domain callers cannot bypass HTTP form validation.

- `name` is required, a string of at most 255 characters and must not be blank after trimming (`NotBlankString`). `code` is optional, at most 64 characters and blank codes are normalised to `null`.
- `display_order` is a required integer from `1` to `academic-structure.max_display_order`. Uniqueness is school-wide for levels, per level for grades, per grade for classes and per class for streams. Levels and grades also enforce school-wide case-insensitive name/code uniqueness; classes and streams enforce it within their parent.
- `is_active` is optional on input and defaults to `academic-structure.default_is_active` on create. Updates merge with the stored record, so omitted fields are unchanged. HTTP rejects `null`; a domain update that supplies `null` also keeps the stored value. Explicit `false` deactivates.
- `configuration` is an optional array on levels, class definitions and offerings; an empty array is stored as `null`. Current validation has no size, depth or key allowlist (see open questions). An offering's `name` is optional and blank values become `null`.
- Duplicate checks exclude the record being updated and use `LOWER(...)` for case-insensitive labels across database drivers. The database unique constraints remain the final guard.
- Every write runs in a transaction and locks the school row with `lockForUpdate()` first. Parents are then resolved with a school-scoped query before their row locks are taken. SQLite serialises writers, so the lock is a no-op there.
- One class cannot be offered twice in the same academic year, and two named offerings in one year cannot share a name. Different classes may have unnamed offerings: SQL treats multiple `NULL` names as distinct; the application still blocks a duplicate unnamed offering for the same class.
- Academic-year references must belong to the current school. Foreign or missing parents produce generic validation messages; update targets outside the school raise `AuthorizationException` in domain actions.

### Configuration flags (`config/academic-structure.php`)

| Key | Current value | Meaning |
| --- | --- | --- |
| `max_display_order` | `10000` | Upper bound for `display_order` validation. |
| `default_is_active` | `true` | Value used when create input omits `is_active`. |
| `require_active_parent` | `true` | A new grade, class or stream cannot be attached to an inactive level, grade or class respectively. |
| `allow_inactive_class_offering` | `false` | When `true`, an offering write may reference an inactive class definition. |

All four are **PROPOSED** engineering safeguards, chosen for structural integrity rather than sourced policy, and configurable so a verified decision can change them without a code change. Deactivating a parent is intentionally allowed: inactive records remain available for historical structures; the flags only govern new associations. Offering writes (create and update) honour `allow_inactive_class_offering`.

## Evidence basis and assumptions

- **EVIDENCE (STR-05, indexed excerpt; STR-04, UNESCO):** documented stage nomenclature and a 2-7-4-2-3+ description exist. Neither is encoded as a rule or seed in this module.
- **EVIDENCE (STR-15, documented practice):** some schools describe A-Level "Science, Commercial and Arts streams". This is one documented instance, not a national definition.
- **PROPOSED (PF-011, PF-015, PF-017 in Findings 02):** configurable structural units and a stage model with an explicit but unresolved stream semantic. The hierarchy implemented here is the structural subset of that research proposal.
- **UNRESOLVED (STR-21 / research finding 21 in Findings 02):** "stream" is used both for A-Level subject pathways and for parallel classes. No authoritative national definition or count was retrieved; `Stream` carries no semantic field and schools may define none.
- **UNRESOLVED (finding 22 in Findings 02):** the Grade ↔ Form crosswalk remains plausible but unconfirmed; no mapping table was added.
- **PROPOSED:** active-parent checks, ordering bounds, duplicate rules and case-insensitive label checks are engineering integrity decisions, not evidence-backed requirements.
- **Not deployed:** this is a local backend slice; no production rollout is claimed. See [deployment strategy](../../operations/deployment.md).

## Interfaces

Routes are isolated in `routes/academic-structure.php`, loaded by `AcademicStructureServiceProvider` under `web`, `auth` and `school.context` with the `/api` prefix. `php artisan route:list --path=api/academic-structure --json` returned **16 route objects / 21 method–path bindings** (six `GET`, five `POST`, five `PUT|PATCH` match routes; each match route registers both aliases), exit `0`. The table follows the route file exactly; query parameters are shown in the Purpose column.

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/api/academic-structure/levels` | List the school's educational levels by display order and ID |
| `POST` | `/api/academic-structure/levels` | Create an educational level |
| `PUT\|PATCH` | `/api/academic-structure/levels/{educationalLevel}` | Update an owned educational level |
| `GET` | `/api/academic-structure/grade-levels` | List all the school's grade levels by display order and ID |
| `GET` | `/api/academic-structure/levels/{educationalLevel}/grade-levels` | List grades under one owned level |
| `POST` | `/api/academic-structure/levels/{educationalLevel}/grade-levels` | Create a grade in the route's level |
| `PUT\|PATCH` | `/api/academic-structure/grade-levels/{gradeLevel}` | Update an owned grade level |
| `GET` | `/api/academic-structure/grade-levels/{gradeLevel}/classes` | List classes under one owned grade |
| `POST` | `/api/academic-structure/grade-levels/{gradeLevel}/classes` | Create a class in the route's grade |
| `PUT\|PATCH` | `/api/academic-structure/classes/{classDefinition}` | Update an owned class definition |
| `GET` | `/api/academic-structure/classes/{classDefinition}/streams` | List streams under one owned class |
| `POST` | `/api/academic-structure/classes/{classDefinition}/streams` | Create a stream in the route's class |
| `PUT\|PATCH` | `/api/academic-structure/streams/{stream}` | Update an owned stream |
| `GET` | `/api/academic-structure/class-offerings` | List offerings; optional owned `academic_year_id` query filter |
| `POST` | `/api/academic-structure/class-offerings` | Offer a class in an owned academic year |
| `PUT\|PATCH` | `/api/academic-structure/class-offerings/{classOffering}` | Update an owned offering |

**Request/response shape.** Requests are JSON objects containing the writable fields shown in the data model. Create responses use `201` with a singular envelope (`educational_level`, `grade_level`, `class_definition`, `stream`, `class_offering`). Update responses use `200` with the same singular envelope. List responses use `200` with `school_id` and a plural envelope (`educational_levels`, `grade_levels`, `classes`, `streams`, `class_offerings`). List endpoints return all owned rows: there is no pagination and no `is_active` filter. Nested reads add the parent ID to the envelope.

Domain actions live under `app/Domains/AcademicStructure/Actions/` (`Create*`/`Update*` for each of the five entities) and use `GuardsAcademicStructure` for ownership, active-parent and duplicate checks. The controller validates requests, resolves `SchoolContext` and delegates to those actions. On **creates**, parent IDs come from the route, never from client input. On **updates**, parent IDs are accepted from the request but resolved through the current school, so reparenting is possible only between owned parents.

## Isolation behaviour

- `SchoolContext::assertCurrent()` supplies the school from the server-resolved context. The existing middleware accepts `X-School-Id` or a body `school_id`, but this module `prohibits` client `school_id` on every write request and on the offerings list query (`422`); an unauthorised `X-School-Id` header is rejected with `403` and ambiguous selection with `400`. There is no fallback to a client value or another tenant. See [ResolveSchoolContext](../../app/Http/Middleware/ResolveSchoolContext.php).
- All five models opt into `ScopesToCurrentSchool` without a global scope. Reads use `forCurrentSchool()`, which fails closed with `AuthorizationException` when no school is authorised; nested reads scope the relation again.
- Route-parent lookups use `forCurrentSchool()->findOrFail()`, so foreign IDs return `404`. Domain writes resolve parents against the resolved school and return the same generic "not available" message for missing and foreign IDs, so records cannot be probed through validation errors. Foreign names are not echoed.
- `school_id` is not mass-assignable on any model; actions set it from the resolved `School` after validation. Composite foreign keys block cross-school parents at the database layer. Parent row locks are taken only after the school-scoped lookup.
- Tenant-varying JSON responses send `Cache-Control: no-store` and `Vary: X-School-Id, school_id`, mirroring the academic calendar API.
- No roles or permissions are enforced in this slice: any authenticated user with a valid school membership can use the endpoints until the permissions slice is implemented. This is a known medium-severity limitation for exposure beyond a foundation review.

## Implementation decisions

| ID | Decision | Basis |
| --- | --- | --- |
| AS-D-01 | Store no seeded stages, grade names, classes or streams. | EVIDENCE names are examples only (STR-05); no universal model evidenced. |
| AS-D-02 | Include `school_id` in every parent foreign key. | PROPOSED shared-database boundary with defence in depth; verified by schema tests. |
| AS-D-03 | Make streams optional and semantically neutral. | UNRESOLVED STR-21. |
| AS-D-04 | Keep integrity switches in configuration. | PROPOSED safeguards; reversible without code changes. |
| AS-D-05 | Reuse the existing `auth` + `school.context` middleware; add no new permissions. | Out of scope for this foundation slice. |
| AS-D-06 | Expose create/update/list only; rely on DB cascades for deletion semantics. | No delete/archive decision has been made. |

## Unresolved questions for product and engineering

- **Product:** does "stream" mean a parallel class, an A-Level subject pathway, or both, and may a school define its own meaning (STR-21)?
- **Product:** should a Grade ↔ Form crosswalk be modelled, and if so where does it come from (finding 22)?
- **Product:** should any stage names be offered as optional templates, or must every school start from an empty structure?
- **Product:** is an explicit delete/archive API needed, and what happens to historical offerings?
- **Engineering:** should `require_active_parent` remain `true`, and should deactivating a parent be blocked when active children exist?
- **Engineering:** is `LOWER(...)` application uniqueness sufficient, or should a normalized/generated lowercase column or expression index be added?
- **Engineering:** should `configuration` have a serialized-size cap, depth limit or key allowlist?
- **Engineering:** is the offering `name` needed at all, and should multiple unnamed offerings for different classes in one year be explicitly allowed?
- **Engineering:** what reordering semantics should `display_order` have (gap handling and moves between parents)?
- **Engineering:** should list endpoints support filtering by `is_active` or pagination once structures grow?

## Verification evidence

All checks below were run from the isolated worktree `/tmp/opencode/p1-academic-structure-foundation` using Compose project `zsmm-structure`; commands, exit codes and output are recorded in `/tmp/opencode/final-verify2.txt` (local evidence, **not** GitHub Actions).

| Command | Exit | Result |
| --- | ---: | --- |
| `docker compose -p zsmm-structure -f docker-compose.yml config -q` | 0 | Compose config valid |
| `docker compose -p zsmm-structure run --rm --no-deps app ./vendor/bin/pest tests/Feature/Domains/AcademicStructure --colors=never` | 0 | 41 passed, 243 assertions |
| `docker compose -p zsmm-structure run --rm --no-deps app php artisan test` | 0 | 137 passed, 595 assertions |
| `docker compose -p zsmm-structure run --rm --no-deps app ./vendor/bin/pint --test` | 0 | 135 files, no style issues |
| `docker compose -p zsmm-structure run --rm --no-deps app ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G` | 0 | `[OK] No errors` |
| `docker compose -p zsmm-structure run --rm --no-deps app composer validate --strict` | 0 | `./composer.json is valid` |
| `docker compose -p zsmm-structure run --rm --no-deps app composer audit --locked` | 0 | No security vulnerability advisories |
| `docker compose -p zsmm-structure run --rm --no-deps app php artisan migrate:fresh --env=testing --force --no-interaction` | 0 | All migrations ran on SQLite |
| `docker compose -p zsmm-structure run --rm --no-deps app php artisan route:list --path=api/academic-structure --json` | 0 | 16 route objects |
| `git diff --check` | 0 | No whitespace errors |

The 33 named cases in the three AcademicStructure Pest files expand to 41 instances through datasets. A read-only tenant-safety review found no high or critical findings; scoped parent locking and explicit-`null` `is_active` handling were hardened in response, with a regression test for the latter. A fresh-reader documentation review was performed by a read-only agent and its factual findings were applied. No human reader review and no remote CI result is claimed.
