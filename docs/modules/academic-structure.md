# Academic Structure foundation

**Status:** implemented backend foundation integrated with the current authorization and audit foundations, not deployed and not user-facing. Rules marked **PROPOSED** are either research candidates awaiting confirmation or provisional engineering safeguards; they are not approved product requirements.
**Evidence basis:** [Findings 02 — school structures, stages and organisational units](../../research/findings/02-organisational-structures.md) (source IDs `STR-*`), [module specification template](../../templates/module-specification.md).
**Not included:** learner/student records, enrolment, attendance, fees, assessment, reporting, HR, boarding or a user interface. Authentication, school context, roles and permissions are reused from the existing foundations rather than reimplemented here.

## Purpose and non-goals

A school defines its own structural hierarchy: educational level → grade level → class definition → optional stream. A class definition may be offered in one of the school's academic years (`class_offerings`). Offerings reference class definitions, not streams. The module stores structure only; a class definition holds no learners and an offering holds no enrolment or progression.

- **EVIDENCE (STR-05, indexed excerpt):** MoPSE statistical nomenclature includes ECD A, ECD B, Grades 1–7, Forms 1–4, Lower 6 and Upper 6. These names are examples for configuration only. The module seeds no levels, grades, classes or streams and makes no claim about a universal national model.
- **No universal assumptions:** no prescribed stage count, no Grade ↔ Form crosswalk, no fixed class size, no mapping of a stream to a subject pathway or a parallel group.
- **No school functionality beyond structure:** no admissions, attendance, fees, learner records, assessment, reporting, HR or boarding.
- **Existing access foundations:** endpoints rely on the existing `auth` + `school.context` middleware and add `EnsurePermission` at the route boundary. No new login, role or permission model is introduced.
- **API-only bootstrap:** there is no seeder, CLI command or UI for creating the initial structure. Schools must use the JSON endpoints.
- **No delete surface:** cascade rules exist in the database, but this slice exposes no delete/archive endpoint. History retention after deletion is an unresolved product decision.

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
- Every write runs in a transaction and locks the school row with `lockForUpdate()` first. Parents are then resolved with a school-scoped query before their row locks are taken. SQLite serialises writers, so the lock is a no-op there. This activates ADR-0002's locking review trigger; SQLite does not prove concurrent MySQL lock behaviour.
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

Routes are isolated in `routes/academic-structure.php`, loaded by `AcademicStructureServiceProvider` under `web`, `auth` and `school.context` with the `/api` prefix. Every route also applies `EnsurePermission`: reads require `academic_structure.view`, writes require `academic_structure.manage`. `php artisan route:list --path=api/academic-structure --json` returned **16 route objects / 21 declared method–path bindings** (six `GET|HEAD` objects counted once, five `POST`, five `PUT|PATCH` objects registering both aliases), exit `0`; Laravel expands those to 27 individual HTTP methods. The table follows the route file exactly; query parameters are shown in the Purpose column. Request fields, status codes and response shapes are documented in the [Academic Structure API](../../api/academic-structure.md); a regression test now asserts the expected permission on all 21 method–path bindings.

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

**Request/response shape.** Requests are JSON objects containing the writable fields shown in the data model. Create responses use `201` with a singular envelope (`educational_level`, `grade_level`, `class`, `stream`, `class_offering`). Update responses use `200` with the same singular envelope. The class envelope key is `class`, not `class_definition`; the model and table remain named `ClassDefinition`. List responses use `200` with `school_id` and a plural envelope (`educational_levels`, `grade_levels`, `classes`, `streams`, `class_offerings`). List endpoints return all owned rows: there is no pagination and no `is_active` filter. Nested reads add the parent ID to the envelope.

Domain actions live under `app/Domains/AcademicStructure/Actions/` (`Create*`/`Update*` for each of the five entities) and use `GuardsAcademicStructure` for ownership, active-parent and duplicate checks. The controller validates requests, resolves `SchoolContext` and delegates to those actions. On **creates**, parent IDs come from the route, never from client input. On **updates**, parent IDs are accepted from the request but resolved through the current school, so reparenting is possible only between owned parents.

## Permissions and audit

- `academic_structure.view` is granted to `school_member`, `school_editor` and `school_admin` (the last via `*`). `academic_structure.manage` is granted to `school_editor` and `school_admin`. `role_viewer`, roleless users and users whose matching role is assigned in another school are denied.
- Permissions are resolved by `SchoolPermissions` from active role assignments in the server-resolved school. The middleware never reads a school or role from client input.
- All ten create/update actions append a deliberate `AuditEvent` through `RecordAuditEvent`, in category `school_configuration` with source `domain`. Actions are `educational_level.created|updated`, `grade_level.created|updated`, `class_definition.created|updated`, `stream.created|updated` and `class_offering.created|updated`.
- Audit snapshots contain selected scalar fields. School-defined `configuration` payloads are represented by `configuration_present` rather than copied verbatim. Rejected validation and rejected ownership checks emit no structure audit event. Events are scoped to the server-resolved school and use the existing authenticated actor resolver.

## Isolation behaviour

- `SchoolContext::assertCurrent()` supplies the school from the server-resolved context. The existing middleware accepts `X-School-Id` or a body `school_id`, but this module `prohibits` client `school_id` on every write request and on the offerings list query (`422`); an unauthorised `X-School-Id` header is rejected with `403` and ambiguous selection with `400`. There is no fallback to a client value or another tenant. See [ResolveSchoolContext](../../app/Http/Middleware/ResolveSchoolContext.php).
- All five models opt into `ScopesToCurrentSchool` without a global scope. Reads use `forCurrentSchool()`, which fails closed with `AuthorizationException` when no school is authorised; nested reads scope the relation again.
- Route-parent lookups use `forCurrentSchool()->findOrFail()`, so foreign IDs return `404`. Domain writes resolve parents against the resolved school and return the same generic "not available" message for missing and foreign IDs, so records cannot be probed through validation errors. Foreign names are not echoed.
- `school_id` is not mass-assignable on any model; actions set it from the resolved `School` after validation. Composite foreign keys block cross-school parents at the database layer. Parent row locks are taken only after the school-scoped lookup.
- Tenant-varying JSON responses send `Cache-Control: no-store` and `Vary: X-School-Id, school_id`, mirroring the academic calendar API.
- Structure permissions are enforced at the route boundary from active role assignments in the server-resolved school. A valid school membership alone is not sufficient for writes; `school_member` can read but cannot write. Permission decisions are not delegated to client headers or request bodies.

## Implementation decisions

| ID | Decision | Basis |
| --- | --- | --- |
| AS-D-01 | Store no seeded stages, grade names, classes or streams. | EVIDENCE names are examples only (STR-05); no universal model evidenced. |
| AS-D-02 | Include `school_id` in every parent foreign key. | PROPOSED shared-database boundary with defence in depth; verified by schema tests. |
| AS-D-03 | Make streams optional and semantically neutral. | UNRESOLVED STR-21. |
| AS-D-04 | Keep integrity switches in configuration. | PROPOSED safeguards; reversible without code changes. |
| AS-D-05 | Reuse the existing `auth` + `school.context` middleware and add two catalogue permissions to the existing RBAC model. | The current mainline has roles and permissions; the canonical foundation predated them. |
| AS-D-06 | Expose create/update/list only; rely on DB cascades for deletion semantics. | No delete/archive decision has been made; hard cascade deletion is a known product risk. |
| AS-D-07 | Append deliberate `school_configuration` audit events for every structure write. | The current mainline has an audit foundation; the canonical foundation predated it. |

## Unresolved questions for product and engineering

- **Product:** does "stream" mean a parallel class, an A-Level subject pathway, or both, and may a school define its own meaning (STR-21)?
- **Product:** should a Grade ↔ Form crosswalk be modelled, and if so where does it come from (finding 22)?
- **Product:** should any stage names be offered as optional templates, or must every school start from an empty structure?
- **Product:** is an explicit delete/archive API needed, and what happens to historical offerings? Today deletion is only a database cascade from a school or ancestor; no archive API or immutability rule exists, so historical structure references can disappear while audit events remain. This is an unresolved product decision, not an approved policy.
- **Engineering:** should `require_active_parent` remain `true`, and should deactivating a parent be blocked when active children exist?
- **Engineering:** is `LOWER(...)` application uniqueness sufficient, or should a normalized/generated lowercase column or expression index be added? MySQL 8 fresh migrations and rollback are verified below, but no non-ASCII collation comparison was exercised, so collation-dependent duplicate behaviour remains untested.
- **Engineering:** the MySQL 64-character index-name fix is verified manually but is not pinned by an automated schema test. A fresh MySQL migration check remains required before relying on schema changes in a new deployment.
- **Engineering:** full reverse-batch rollback is verified, but a partial rollback of `2026_11_28_000000_add_school_scoped_key_to_academic_years.php` while `class_offerings` still exists would fail because that composite key is required by the offering foreign key. Treat that migration as non-independently reversible after the structure tables are created.
- **Engineering:** should `configuration` have a serialized-size cap, depth limit or key allowlist?
- **Engineering:** is the offering `name` needed at all, and should multiple unnamed offerings for different classes in one year be explicitly allowed?
- **Engineering:** what reordering semantics should `display_order` have (gap handling and moves between parents)?
- **Engineering:** should list endpoints support filtering by `is_active` or pagination once structures grow?

## Verification evidence

All checks below were run from the isolated worktree `/tmp/opencode/p1-academic-structure-integration` using Compose project `p1-academic-structure-integration` with override `/tmp/opencode/docs-compose-p1-academic-integration.override.yaml` (MySQL on `127.0.0.1:3308`, nginx on `127.0.0.1:8001`). Logs are in `/tmp/opencode/academic-*.log`. This is local evidence, **not** GitHub Actions or remote CI.

| Command | Exit | Result |
| --- | ---: | --- |
| `docker compose ... config -q` | 0 | Compose config valid |
| `docker compose ... run --rm --no-deps app ./vendor/bin/pest tests/Feature/Domains/AcademicStructure --colors=never` | 0 | 50 passed, 282 assertions |
| `make verify COMPOSE="docker compose ..."` | 0 | Pint: 207 files clean; PHPStan/Larastan: `[OK] No errors`; Composer audit: no advisories; full Pest: 243 passed, 990 assertions |
| `docker compose ... exec -T app composer validate --strict` | 0 | `./composer.json is valid` |
| `docker compose ... exec -T mysql sh -c 'mysql ... -e "SELECT VERSION();"'` | 0 | MySQL `8.0.46` |
| `docker compose ... exec -T app php artisan migrate:fresh --force --no-interaction` | 0 | All 21 migrations ran on MySQL 8 (see note below) |
| `docker compose ... exec -T app php artisan migrate:status` | 0 | All 21 migrations reported `Ran` in batch 1 |
| `docker compose ... exec -T app php artisan migrate:rollback --force --no-interaction` | 0 | All 21 migrations rolled back on MySQL 8 |
| `docker compose ... run --rm --no-deps app php artisan route:list --path=api/academic-structure --json` | 0 | 16 route objects / 21 declared bindings / 27 expanded HTTP methods; every route has `EnsurePermission` |
| `git diff --check` | 0 | No whitespace errors |
| Post-merge `make verify COMPOSE="docker compose -p p1-academic-verify -f docker-compose.yml -f /tmp/opencode/academic-verify-compose.override.yaml"` at `9fc4804` | 0 | Pint: 207 files clean; PHPStan/Larastan: `[OK] No errors`; Composer audit: no advisories; full Pest: 243 passed, 990 assertions |
| Post-merge focused Pest files (API, isolation, permissions, audit, schema) | 0 | 24 / 110, 11 / 110, 6 / 15 (implementation baseline), 2 / 14 and 7 / 33 assertions respectively |
| Post-merge MySQL 8.0.46 `migrate:fresh` / `migrate:status` / `migrate:rollback` / status-after | 0 | Structure migrations applied, reported `Ran`, rolled back, then reported `Pending` |
| Docs/API branch `make verify COMPOSE="docker compose -p p1-academic-tracker-verify -f docker-compose.yml -f /tmp/opencode/academic-tracker-verify-compose.override.yaml"` plus focused permission suite | 0 | Pint: 207 files clean; PHPStan/Larastan: `[OK] No errors`; Composer audit: no advisories; full Pest: 244 passed, 1007 assertions; permission suite: 7 passed, 32 assertions after the all-route regression test |

The Pest suite is forced to in-memory SQLite by `phpunit.xml`; the MySQL results above are **migration checks only**, not Pest coverage against MySQL. No non-ASCII collation case was run. Fresh-install verification initially failed because four generated index names exceeded MySQL's 64-character identifier limit (one in the existing school identity migration and three in Academic Structure); explicit short names now avoid that limit without changing constraints.

The post-merge re-run used Compose project `p1-academic-verify` with override `/tmp/opencode/academic-verify-compose.override.yaml` (MySQL on `127.0.0.1:3312`, nginx on `127.0.0.1:8012`) and logs `/tmp/opencode/academic-verify-*.log`. The docs/API branch re-run used `p1-academic-tracker-verify` with override `/tmp/opencode/academic-tracker-verify-compose.override.yaml` (MySQL on `127.0.0.1:3313`, nginx on `127.0.0.1:8013`) and logs `/tmp/opencode/academic-tracker-verify-*.log`. No MySQL concurrency or lock-contention test was run. Separately, GitHub checks on PR #59 were read remotely: `quality`, `tests`, `docker` and GitGuardian were `pass`; CodeRabbit was still `pending` at the final read. These remote results are not converted from the local evidence, and no human review or approval is claimed.

Read-only data-model, API/test, and replay-scope reviews were completed before implementation. The first resolved the cross-school composite-key and rollback-batch risks, the second drove the permission/audit integration, and the third confirmed no shipped frontend, docs, config or Livewire work was reverted by the replay. No human reader review is claimed.
