# B-07 — Domain Architecture Audit

**Task:** B-07 domain architecture audit (audit and documentation only)
**Audited baseline:** `9f84acbfae05b95fe4e96f828865986441d1de21` (`origin/main`, fetched 2026-11-28)
**Working branch:** `feature/backend-domain-architecture-audit`
**Isolated worktree:** `/home/praisetechzw/.local/share/opencode/worktree/b07-domain-architecture-audit`
**Evidence classes:** **VERIFIED** = directly observed in code, repository documents or command output; **PROPOSED** = recommended change, not current behaviour; **ASSUMPTION** = unvalidated premise; **UNRESOLVED** = requires a product/owner decision.
**Scope limit:** No application code, migration, permission, audit implementation or documentation outside this report was changed. No PR was merged. This task does not modify application code; application test suites were **not** run for this audit and no such claim is made.

## 1. Executive summary

The audited baseline is a coherent **modular Laravel monolith** with genuinely strong foundations: server-resolved school context derived from membership, fail-closed opt-in tenant scopes, transactions with row locks backed by unique constraints, an append-only audit model with sanitisation, config-driven school-scoped permissions, and unusually good isolation/permission test coverage for the domains that exist. No confirmed cross-tenant data leak was found on reviewed HTTP paths, and this report does not claim one.

The architecture is nevertheless **fragile at its boundaries**. Almost every safety property is opt-in: tenant scoping has no global safety net, permission middleware is applied inconsistently, audit events are written only where a domain deliberately calls the recorder, and domain rules are copied across FormRequests, actions, controllers and frontend adapters. One concrete data-integrity defect exists today: the settings revision path can write an empty string into a non-nullable `date` column. Two authorization gaps exist today: school settings have no permission at all on API, Livewire or domain paths, and a disabled/suspended account with an existing session still satisfies `SchoolPermissions` at the audited baseline. The database enforces tenant ownership for Academic Structure through composite foreign keys but does not do so for `academic_periods` or `school_setting_revisions`.

The system is **not yet ready to absorb the planned Zimbabwe school modules without redesign**. The tenant, permission and audit plumbing is reusable, but no decision has been made about the shared kernel that those modules need: a person/identity model for learners, guardians and staff; a money/currency representation; a uniform tenant-foreign-key convention; closed-vocabulary enforcement; archival/delete semantics; and an audit event taxonomy. Research evidence for most Zimbabwe-specific workflows remains PROPOSED or UNRESOLVED and must not be treated as a verified requirement.

**Finding counts:** no P0 Critical finding was confirmed; 8 P1 High, 14 P2 Medium and 6 P3 Low findings are recorded in §13. Strengths S-01–S-10 are recorded in §3.

## 2. Architecture overview of the actual current implementation

**VERIFIED.** One Laravel 13 / PHP 8.3 application, one database, one deployable unit (`composer.json`, `docs/architecture/overview.md:5-11`; ADR-0001/ADR-0002). MySQL 8 is the system of record for development/self-hosting; Pest tests use SQLite `:memory:` (`phpunit.xml:27-29`).

| Layer | Actual implementation | Evidence |
| --- | --- | --- |
| HTTP | 13 controllers under `app/Http/Controllers`, 17 FormRequests, 2 middleware (`EnsurePermission`, `ResolveSchoolContext`). Controllers delegate to domain actions via `app(...)`, but also contain inline validation, model queries and payload mappers. | `app/Http/**`; Finding B07-10 |
| Domains | 7 bounded contexts under `app/Domains`: `School`, `Auth`, `Authorization`, `AuditTrail`, `SchoolSettings`, `AcademicCalendar`, `AcademicStructure`. 32 action classes plus models, concerns, rules, services and support classes. | `find app/Domains -path '*/Actions/*.php'`; §6 |
| Identity | `App\Models\User` sits outside the domains and is imported by `Auth`, `Authorization` and `School`; no separate Identity/shared-kernel domain. | `app/Models/User.php:6-7,45-48`; Finding B07-10 |
| Frontend | In-process Blade/Livewire and `app/Frontend` gateway adapters implementing contracts bound in `AppServiceProvider.php:30-42`. They call domain actions and, in several places, domain models directly. | `app/Frontend/**`; `AppServiceProvider.php:30-54` |
| Routes | `routes/web.php` and `routes/console.php` are loaded by `bootstrap/app.php:10-14`; five service providers register their own API route files (`AuthServiceProvider.php:30`, `AcademicCalendarServiceProvider.php:17-19`, `AcademicStructureServiceProvider.php:17-19`, `SchoolSettingsServiceProvider.php:17-19`, `RolesServiceProvider.php:23-25`). Most API routes are unnamed. | `routes/*.php`; Finding B07-18 |
| Authorization | Config catalogue (`config/authorization.php:18-71`), school-scoped role assignments, `SchoolPermissions`, `EnsurePermission`, plus action-level checks. No Gate/Policy classes; FormRequests return `authorize(): true`. | `config/authorization.php`; `app/Domains/Authorization/**`; §8 |
| Tenancy | Request-scoped singleton `SchoolContext`, populated by `ResolveSchoolContext` from `school_user` memberships, cleared after requests; explicit `forSchool()`/`forCurrentSchool()` local scopes on school-owned models; no global scope. | `SchoolContext.php:18-69`; `ScopesToCurrentSchool.php:18-39`; §7 |
| Audit | Append-only `AuditEvent` model/builder, single `AuditEventRecorder` write path, allow-listed/sanitised metadata, deliberate opt-in events inside domain transactions; no HTTP read route. | `app/Domains/AuditTrail/**`; §10 |
| Schema | 21 migrations: framework tables; `schools`, identifiers, statuses, relationships, `school_user`; academic years/periods; account lifecycle columns; settings and revisions; `audit_events`; `role_assignments`; `academic_years(school_id,id)` unique key; `educational_levels`, `grade_levels`, `class_definitions`, `streams`, `class_offerings`. No learner, guardian, finance, attendance, assessment, HR, boarding, timetable, procurement, inventory, library, transport, welfare or messaging tables. | `database/migrations/**`; §12 |
| Tests | 26 test classes (25 Feature, 1 Unit) plus 3 support files; 16 factories. Pest with `RefreshDatabase`; tenant-isolation and permission tests concentrated in `tests/Feature/Domains/**`. CI runs Pint, PHPStan/Larastan, Composer/npm audits, Pest and Docker build; no MySQL job. | `tests/**`; `.github/workflows/ci.yml`; Finding B07-20 |

The documented request flow (`docs/architecture/overview.md:27-32`) is route → middleware → FormRequest → domain action → JSON/view. The implementation follows this for writes in the strongest domains, but §4 and Finding B07-10 record where it does not.

## 3. Strengths

- **S-01 — Fail-closed tenant scopes (VERIFIED).** `ScopesToCurrentSchool.php:33-39` calls `SchoolContext::assertCurrent()`, which throws `AuthorizationException` when no school is authorised (`SchoolContext.php:45-52`). Every inspected school-owned model uses the trait: `SchoolIdentifier.php:19`, `SchoolStatus.php:19`, `SchoolRelationship.php:19`, `AcademicYear.php:24`, `AcademicPeriod.php:24`, all five Academic Structure models at `:32-36`, `SchoolSetting.php:28`, `SchoolSettingRevision.php:27`.
- **S-02 — Server-side tenant selection with membership revalidation (VERIFIED).** `ResolveSchoolContext.php:33-51` derives allowed schools from `user->schools()` and rejects unauthorised, malformed or conflicting selectors; `:123-140` revalidates remembered Livewire school IDs against live memberships. A client ID can only select among already-authorised schools.
- **S-03 — Context lifecycle is request-scoped and cleaned up (VERIFIED).** `AppServiceProvider.php:30` registers the context as a singleton; `ResolveSchoolContext.php:68-80` clears it in `finally` for normal requests and at termination for Livewire updates; `AppServiceProvider.php:52-54` registers the resolver as persistent Livewire middleware. `tests/Feature/Domains/School/SchoolIsolationTest.php:144-152` asserts cleanup.
- **S-04 — Composite tenant foreign keys in Academic Structure (VERIFIED).** `2026_11_28_000002_create_grade_levels_table.php:23-27`, `...000003_create_class_definitions_table.php:24-28`, `...000004_create_streams_table.php:23-27` and `...000005_create_class_offerings_table.php:23-32` constrain each parent to the same `school_id`; schema tests reject foreign parents (`AcademicStructureSchemaTest.php:46-121,154-185`).
- **S-05 — Transaction and lock discipline with a durable backstop (VERIFIED).** `GuardsCalendarIntegrity.php:37-45` and `GuardsAcademicStructure.php:28-31` lock the school row; `CreateAcademicYear.php:39-68`, `CreateAcademicPeriod.php:45-82`, `UpdateAcademicPeriod.php:45-90`, `UpdateSchoolSettings.php:28-64` wrap lock + integrity + write + audit in one transaction; unique constraints back the checks.
- **S-06 — Deterministic current-year resolution with no fuzzy fallback (VERIFIED).** `config/academic-calendar.php:28-32` labels the rule PROPOSED and states there is deliberately no fuzzy fallback; `ResolveCurrentAcademicYear.php:11-43` implements status + date range + deterministic tie-break and returns `null` otherwise.
- **S-07 — Reads do not create tenant data and validation is single-sourced where designed (VERIFIED).** `ResolveSchoolSettings.php:11,20` returns an unsaved default; `UpdateSchoolSettingsRequest.php:22` reuses `ValidateSchoolSettings::rules()`; `AcademicStructureRules.php:9-75` shares field rules and partial-update transforms.
- **S-08 — Append-only, sanitised audit design (VERIFIED).** `AuditEvent.php:41,66-83`, `ImmutableAuditEventBuilder.php:15-60`, `AuditEventRecorder.php:43-64`, `SanitizeAuditData.php:25-38,129-141`, and `AuditScope.php:12-13` (no raw client ID factory). `QueryAuditEvents.php:15-17` deliberately withholds an HTTP route until authorisation exists.
- **S-09 — Authentication hardening (VERIFIED).** `AttemptLogin.php:12-17,27-30,59-88` performs exactly one hash verification on every path and counts failures under a row lock; `AuthenticatedUserResource.php:11-16,32-39` is an allow-list.
- **S-10 — Consistent success codes and tenant headers (VERIFIED).** Creates return 201, delete 204 with no body, reads/updates 200 (`AcademicCalendarController.php:66,106`, `AcademicStructureController.php:64,99,151,186,228`, `RoleAssignmentController.php:57,70-72`); tenant responses set `Cache-Control: no-store` and `Vary` (`AcademicCalendarController.php:202-207`, `AcademicStructureController.php:371-376`, `SchoolSettingsController.php:84-89`, `RoleAssignmentController.php:96-101`).

## 4. Architecture weaknesses

1. **Opt-in safety boundaries.** No global tenant scope, no policy layer, no route-wide permission assertion, no architecture tests enforcing the documented rules. Every new read/write must remember to opt in (B07-02, B07-18).
2. **Duplication across layers and adapters.** Calendar validation and integrity rules exist two or three times with different semantics; tenant, validation and string helpers are copied; API and frontend response mappers have already diverged on concrete fields (B07-09, B07-10, B07-11).
3. **Boundary leakage.** Domain Eloquent models are imported by another domain (Academic Structure → Academic Calendar), by controllers and by frontend adapters; controllers contain inline validation and model queries despite the thin-controller rule (B07-10).
4. **Incomplete authorization surface.** Configured view permissions are not enforced; settings have no permission at all; disabled accounts retain permissions at baseline (B07-01, B07-05, B07-06).
5. **Uneven persistence guarantees.** Composite tenant FKs exist in one domain but not in the calendar period or settings revision tables; case-insensitive uniqueness and temporal/vocabulary rules are application-only and driver-dependent (B07-04, B07-12–B07-15).
6. **Narrow audit perimeter.** Append-only storage is sound, but role changes and CLI bootstrap are not recorded, there is no retention policy, and school deletion conflicts with `RESTRICT` (B07-16, B07-17).
7. **Provisional cross-cutting HTTP contract.** No error envelope/correlation echo, no idempotency and no pagination, while the planned module list assumes these will scale (B07-19).
8. **Testing and evidence gaps.** SQLite-only CI despite MySQL locking/collation dependencies, very thin unit layer, no browser automation, and PF/NFR requirement IDs absent from code/tests (B07-20, B07-25, B07-26).
9. **Documentation drift.** The architecture overview and testing strategy still describe Sprint 0, while Phase 1 and ADR-0003 are accepted and implemented (B07-23).

## 5. Critical risks

Ranked by business impact. **No P0 Critical finding was confirmed at this baseline**; the highest-priority items are P1 High.

| Rank | Risk | Impact if unaddressed | Finding |
| --- | --- | --- | --- |
| 1 | Any school member can read/write school settings and generate audit events; no settings permission exists on HTTP, Livewire or domain paths. | Unauthorised change of locale, timezone, provenance and effective date for an entire school. | B07-01 |
| 2 | Tenant scoping is opt-in with no global guard; future learner/finance queries are one omitted scope away from cross-tenant data. | Cross-tenant PII or financial exposure as modules are added. | B07-02 |
| 3 | Settings revision snapshot casts a null `effective_from` to `''` before writing to a non-nullable `date` column. | The second update of a settings record without a date can fail or corrupt revision history. | B07-03 |
| 4 | `academic_periods` can reference a year owned by another school at the database level. | Imports/raw writes can create contradictory tenant ownership; cascade deletes remove wrong-tenant dependants. | B07-04 |
| 5 | Disabled/suspended accounts with an existing session still pass permission checks at baseline. | Deactivation does not immediately revoke in-session access. | B07-05 |
| 6 | MySQL-specific locking, collation and constraint behaviour is not exercised in CI. | Bugs hidden by SQLite can surface only in the self-hosted production database. | B07-07 |
| 7 | Sensitive future modules have no agreed person/identity, money/currency, archival or audit-taxonomy kernel. | Learner, guardian, fees and HR modules will each invent incompatible models. | B07-08 |

## 6. Domain-boundary analysis

**VERIFIED.** The documented rule is that a domain owns its tables and public actions and that other domains call actions or explicit service interfaces rather than reaching into another domain's models (`docs/architecture/overview.md:25`).

| Domain / layer | Owns | Depends on | Boundary assessment |
| --- | --- | --- | --- |
| `School` | schools, identifiers, statuses, relationships, membership pivot, `SchoolContext`, scopes | `User` (shared kernel), `Auth` enum via `User` | Strong tenant mechanics; internal-only relationship action needs explicit related-school authorisation if exposed (B07-22). |
| `Auth` | account lifecycle, login/lockout, authenticated resource | `App\Models\User`, `School` (membership sync in `CreateUser`) | Login hardening good; status is checked at login only (B07-05). `CreateUser` has no HTTP boundary (B07-22). |
| `Authorization` | role assignments, `SchoolPermissions` | `User`, `School`, config catalogue | Strongest write boundary (non-escalation, school-local roles); role/CLI changes unaudited (B07-16). |
| `SchoolSettings` | settings, revisions, provenance | `School`, `AuditTrail`, config | Sound transaction/versioning; no permission boundary (B07-01), revision FK/date defects (B07-03, B07-12). |
| `AcademicCalendar` | years, periods, integrity guard, current-year resolution | `School`, `AuditTrail`, config | Good locking and constraints; missing composite tenant FK (B07-04) and duplicated rules (B07-09). |
| `AcademicStructure` | levels, grades, classes, streams, offerings, config | **direct `AcademicYear` model import** (`GuardsAcademicStructure.php:83-95`), `School`, `AuditTrail` | Tenant-safe but violates the documented action/service boundary (B07-10). Composite FKs are the strongest in the repo (S-04). |
| `AuditTrail` | append-only events, recorder, query action | `School` model (contract inversion via `AuditScope`), auth actor contracts | Well isolated; opt-in and application-only immutability (B07-17). |
| `Frontend` | contracts, gateways, Livewire screens | Domain actions **and** domain models (`DomainCalendarManagementGateway.php:12-13`, `ServerFrontendDataProvider.php:8-11`, `DomainSchoolSettingsGateway.php:12`) | Adapter intent is documented, but direct model queries bypass the read-action boundary (B07-10). |
| `Http` | controllers, FormRequests, middleware | Domain actions/models, `SchoolContext`, `SchoolPermissions` | Thin-controller rule inconsistently applied (`AcademicCalendarController.php:117-134`, `AcademicStructureController.php:197-222,239-366`) (B07-10). |

## 7. Tenant-isolation assessment

**VERIFIED positives:** membership-derived allow-list and strict selector validation (`ResolveSchoolContext.php:33-51,91-117`); multi-school users must select explicitly (`:43-45`); stale Livewire selections revalidated (`:123-140`); fail-closed `forCurrentSchool()` scopes (S-01); generic error messages for foreign parent IDs (`GuardsAcademicStructure.php:20-24`, `GuardsCalendarIntegrity.php:23-35`); client `school_id` prohibited in FormRequests; composite FKs in Academic Structure (S-04); cross-school role assignment denied (`RolesPermissionsTest.php:37-56`); tenant isolation tests across all school-owned domains.

**Residual risks:** no global scope means unscoped `Model::find()` is silently cross-tenant (B07-02); `school_id` is still fillable on four School-domain models (B07-15); `academic_periods` and `school_setting_revisions` lack composite tenant FKs (B07-04, B07-12); `ResolveSchoolContext` accepts a body `school_id` as a selector while controllers prohibit the same field (B07-28); `SchoolContextController::tenantJson()` uses `Vary: X-School-Id` only although `school_id` is also a selector (B07-11). No confirmed IDOR was found in reviewed paths.

## 8. Authorization assessment

**VERIFIED.** `EnsurePermission.php:22-40` requires an authenticated `User`, a non-null `SchoolContext`, and `SchoolPermissions::has()`; it never reads a school from client input. `SchoolPermissions.php:19-30` requires membership plus an active school-local role assignment; unknown permissions/roles fail closed; `school_admin` expands `*` to the configured catalogue (`config/authorization.php:67-70`).

| Surface | Gate at baseline | Evidence |
| --- | --- | --- |
| Calendar writes | Yes — `academic_calendar.manage` | `routes/academic-calendar.php:9-13,15-20` |
| Calendar reads / validation | No | `routes/academic-calendar.php:7-8,14,21` |
| Academic Structure | Yes — `.view` / `.manage` on all 16 routes | `routes/academic-structure.php:7-53`; `AcademicStructurePermissionTest.php:143-189` |
| School settings (API + Livewire) | **No permission exists** | `routes/school-settings.php:8-10`; `SchoolSettingsController.php:30-37`; `Livewire/SchoolSettings.php:55-76`; B07-01 |
| `/api/school`, `/api/school/identifiers` | No | `routes/web.php:39-41` |
| Role assignments | Yes — `roles.view` / `roles.manage` | `routes/roles.php:9-17`; `AssignSchoolRole.php:32-52` |
| Calendar admin screen | Yes — `academic_calendar.manage` at route and gateway | `routes/web.php:29-31`; `DomainCalendarManagementGateway.php:71-75,120-127` |
| Settings/Roles screens | No page-level gate; role gateway checks in component only | `routes/web.php:32-33`; `DomainRoleAssignmentGateway.php:34-54` |

Declared but unenforced permissions: `school.view`, `school_identifiers.view`, `academic_calendar.view` (`config/authorization.php:19-21`). There is no Gate/Policy layer; FormRequests return `authorize(): true` (e.g. `StoreEducationalLevelRequest.php:12-15`). At the audited baseline `SchoolPermissions::has()` does not re-check account status, so a disabled/suspended user with a live session still passes (B07-05). Role assignment and CLI bootstrap are not audited (B07-16). Open PR #63 proposes fixes to several of these points; see §15.

## 9. Data-integrity assessment

**VERIFIED positives:** date order in calendar FormRequests and actions (`StoreAcademicYearRequest.php:25-26`, `CreateAcademicYear.php:27-28`); name/sequence/within-year/overlap guards (`GuardsCalendarIntegrity.php:47-189`); parent activity and config flags in Academic Structure (`GuardsAcademicStructure.php:109+`); normalised, globally unique school identifiers (`AddSchoolIdentifier.php:69-82`; `2026_11_27_000002...:25`); relationship duplicate/cycle checks under lock (`RecordSchoolRelationship.php:45-115`); settings version/revision under transaction with `unique(['school_id','version'])`.

**Weaknesses:** B07-03 (null date → `''` in a non-nullable date column), B07-04 (`academic_periods` no composite FK), B07-12 (`school_setting_revisions` no composite FK), B07-13 (case-insensitive uniqueness only in application, SQLite/MySQL divergence), B07-14 (temporal validity and closed vocabularies not DB-enforced), B07-15 (`school_id` fillable inconsistency), B07-17 (hard cascades and append-only conflict).

## 10. Auditability assessment

**VERIFIED positives:** append-only model and builder (`AuditEvent.php:41,66-83`; `ImmutableAuditEventBuilder.php:15-60`), single recorder with allow-listed actor types/sources and recursive redaction (`AuditEventRecorder.php:43-64,83-113`; `SanitizeAuditData.php`), server-only actor resolution with `system` fallback (`AuthenticatedActorResolver.php:20-25`), `AuditScope::forSchool()` requiring an already-resolved `School`, and events written inside the same transaction as the domain write (`CreateAcademicYear.php:54-65`, `UpdateSchoolSettings.php:56-61`). Audit redaction explicitly covers safeguarding/medical patterns (`config/audit.php:58-61`).

**Gaps:** role assignment/removal and `roles:assign` write no events (B07-16); no retention, legal-hold or archival policy; immutability is application-layer only, and `audit_events.school_id` uses `RESTRICT` while all other school-owned tables cascade (B07-17); `QueryAuditEvents` is paginated and school-scoped but has no HTTP route while navigation lists "Audit logs" (B07-27); correlation IDs are stored but not echoed in errors or logs (B07-19).

## 11. Performance and scalability assessment

- **VERIFIED:** `ValidateAcademicCalendar.php:66-74` performs an O(n²) pairwise overlap scan in PHP over all periods, duplicating the indexed SQL check at `GuardsCalendarIntegrity.php:168-189` (B07-09).
- **VERIFIED:** all HTTP list endpoints use `->get()`; the only `paginate()` is `QueryAuditEvents.php:35`, which has no route. `AuditEventQuery.php:48-50` already contains a reusable 1–100 `perPage` bound (B07-19).
- **VERIFIED:** every calendar write serialises on the school row (`GuardsCalendarIntegrity.php:42-45`) and Academic Structure locks the school and parents (`GuardsAcademicStructure.php:28-31`). This is safe for the current slice but is a per-school throughput ceiling (PROPOSED to revisit only with measurements).
- **VERIFIED:** frontend adapters re-run the same queries as API controllers and add an extra existence query (`ServerFrontendDataProvider.php:99-105,115-130,154-160`); no N+1 was observed in the mapped collections, and data volume is currently small.
- **ASSUMPTION:** no load or concurrency measurements were taken in this documentation-only audit; scalability claims beyond the code observations above are not made. SQLite tests do not prove MySQL lock behaviour (B07-07).

## 12. Future-module readiness assessment

**VERIFIED positives:** configuration-driven vocabularies with explicit PROPOSED/UNRESOLVED comments (`config/schools.php`, `config/academic-calendar.php`, `config/academic-structure.php`, `config/school-settings.php`, `config/authorization.php`); frontend contract/adapter boundary; honest "not built" screens (`config/frontend.php:7-11`); structural anchors (year → level → grade → class → stream → offering) with tenant-owned keys.

| Future area | Current support | Classification / missing decision |
| --- | --- | --- |
| Learner management | School context, permissions plumbing, audit recorder; no person/learner model or permissions. | PROPOSED foundation only; BLOCKED on person/identity decision (B07-08). |
| Admissions | No domain, table, permission or approved requirement; excluded by decision log D-01/D-02. | UNRESOLVED / out of scope. |
| Guardian relationships | No person model; privacy sources cited but applicability unresolved (U-01/U-02). | PROPOSED research; UNRESOLVED legal/consent rules. |
| Enrolment and progression | Structural anchors and academic years exist; no enrolment state, history or progression rules. | Partial foundation; business rules UNRESOLVED. |
| Attendance | No domain/table; calendar periods are configurable and source-aware. | No foundation; UNRESOLVED rules. |
| Assessment and examinations | No domain; `PF-031` exam-window candidate only. | PROPOSED / UNRESOLVED. |
| ZIMSEC workflows | No domain; `OFF-07` ZIMSEC sources indexed/unreachable, conflicts `C-06`/`C-09`. | UNRESOLVED; do not claim ZIMSEC compliance. |
| Fees, levies, receipts, statements | No finance domain; `config/school-settings.php:11-15` explicitly excludes finance; `PF-040`/`PF-048` PROPOSED. | No foundation; money model decision required (B07-08). |
| Multi-currency financial records | No currency column or code anywhere in `app/`, `database/` or `config/`; `PRODUCT.md` calls for configurability. | ASSUMPTION as product constraint, not verified law; money/currency decision required. |
| Boarding | No domain; `PF-018` PROPOSED; `LAW-03`/`OFF-05` cited. | No foundation; PROPOSED. |
| Staff and HR | `users` is an account, not a person record; no HR domain. | No foundation; identity decision required. |
| Timetables | Years, periods, levels, classes, streams and offerings exist; no period/room/teacher/timetable model. | Partial structural foundation only. |
| Procurement | No code or requirement-candidate evidence found. | UNRESOLVED; no evidence to design from. |
| Inventory | No code or requirement-candidate evidence found. | UNRESOLVED; no evidence to design from. |
| Library | No code or requirement-candidate evidence found. | UNRESOLVED; no evidence to design from. |
| Transport | No code or requirement-candidate evidence found. | UNRESOLVED; no evidence to design from. |
| Student welfare | Audit redaction covers safeguarding/medical patterns; no welfare domain. | PROPOSED safeguard only; UNRESOLVED requirements. |
| Parent communication | No messaging, consent or notification model; parent/guardian only in role/stakeholder candidates. | No foundation; UNRESOLVED. |
| Reporting | EMIS/census sources and `PF-003`/`PF-041`/`NFR-018` captured; many primary PDFs unfetched; no query/read model. | VERIFIED research capture; implementation PROPOSED/UNRESOLVED. |

**Verdict:** the current architecture can reuse tenancy, permission, audit and structural-anchor patterns, but **cannot support most listed modules without major design decisions**. The blocking shared-kernel decisions are itemised in B07-08 and §18.

## 13. Prioritized findings

Each finding carries classification, evidence, risk, impact, recommended remediation, affected files/components, dependencies and verification strategy. Priorities reflect architectural risk at the audited baseline, not severity of an exploited incident.

### P0 Critical

**No P0 findings confirmed.** No unauthenticated administrative path, confirmed cross-tenant data leak, or destructive application-path defect was observed. This is a positive finding, not a gap in coverage; the P1 items below are the highest actionable priorities.

### P1 High

#### B07-01 — School settings have no permission boundary on API, Livewire or domain paths (P1, VERIFIED)

- **Evidence:** `routes/school-settings.php:8-10` has no `EnsurePermission`; `config/authorization.php:18-27` has no `school_settings.*` permission; `SchoolSettingsController.php:30-37` calls `UpdateSchoolSettings` with only `assertCurrent()`; `app/Livewire/SchoolSettings.php:55-76` calls `gateway()->update()` with no permission check; `DomainSchoolSettingsGateway.php:48-59` only asserts context. `tests/Feature/Frontend/SchoolSettingsScreenTest.php:17-25` creates a member with **no role assignment**, then `:48-66` shows that actor saving settings and writing the database. Contrast `DomainCalendarManagementGateway.php:71-75,120-127`.
- **Risk:** any authenticated user with school membership — including a role-less member — can read revisions and change tenant locale/timezone/provenance, and those writes emit audit events.
- **Impact:** unauthorised configuration change and misleading audit provenance; the settings domain cannot be denied with the current permission catalogue.
- **Recommended remediation (PROPOSED):** add `school_settings.view` / `school_settings.manage` to the catalogue, apply `EnsurePermission` to all three routes, add a `canManage()` check in the gateway/component for UI state, and enforce the same permission inside `UpdateSchoolSettings` so API, Livewire and future callers share one boundary.
- **Affected files/components:** `config/authorization.php`, `routes/school-settings.php`, `SchoolSettingsController`, `Livewire/SchoolSettings`, `SchoolSettingsGateway` + `DomainSchoolSettingsGateway`, `UpdateSchoolSettings`.
- **Dependencies:** owner decision on role grants; `SchoolPermissions`; `AuditTrail`; coordination with open PR #63.
- **Verification strategy:** permission tests mirroring `AcademicCalendarPermissionTest.php:67-104` for all three API routes (no role, member, editor, admin, foreign-school role) plus a Livewire test that a role-less member cannot save.

#### B07-02 — Tenant isolation has no global safety net (P1, VERIFIED design; PROPOSED risk)

- **Evidence:** `ScopesToCurrentSchool.php:18-39` provides only local scopes; repository grep found no `addGlobalScope`; `docs/architecture/overview.md:40` states "There is no global scope: unscoped Eloquent queries are not automatically tenant-filtered". Some actions use raw `whereKey` plus a manual check (`UpdateAcademicYear.php:43-48`, `UpdateAcademicPeriod.php:48-53`) while reads rely on scoped helpers.
- **Risk:** a new `Model::find()`/query on a tenant-owned table silently crosses schools; existing tests protect only known paths.
- **Impact:** high for future learner, guardian and finance modules where a single missed scope exposes cross-tenant PII/payment data.
- **Recommended remediation (PROPOSED):** add a Pest architecture test enumerating every model with a `school_id` column and asserting use of `ScopesToCurrentSchool` plus scoped query helpers; consider a global scope with an explicit `withoutSchoolScope()` for trusted console/import paths after an ADR-level decision.
- **Affected files/components:** all school-owned models, actions, controllers, frontend gateways, future module templates.
- **Dependencies:** console/seed/import paths that legitimately query across schools; `SchoolContext`.
- **Verification strategy:** architecture test plus per-module isolation tests asserting foreign IDs return 404/422 without data disclosure.

#### B07-03 — Settings revision can write `''` into a non-nullable date column (P1, VERIFIED code path; PROPOSED runtime impact)

- **Evidence:** `SchoolSetting.php:94-101` returns `'effective_from' => (string) $this->getRawOriginal('effective_from')`, so `null` becomes `''`; `UpdateSchoolSettings.php:42-50` writes that into `SchoolSettingRevision`; `database/migrations/2026_11_27_000012_create_school_setting_revisions_table.php:20` declares `$table->date('effective_from')` (not nullable); `ValidateSchoolSettings.php:34` permits `effective_from` to be omitted/null. Existing two-update tests always set the date (`SchoolSettingsTest.php:73-100`).
- **Risk:** the second update of a settings record that began without `effective_from` inserts an empty string into a DATE column.
- **Impact:** MySQL strict mode likely rejects the insert and breaks the settings write; SQLite may store an invalid value; revision fidelity is lost either way.
- **Recommended remediation (PROPOSED):** make the revision column nullable and pass `?string` through `snapshot()`/`UpdateSchoolSettings.php:49` without casting null to `''`; add a regression test for two consecutive updates where the first omits the date.
- **Affected files/components:** `SchoolSetting::snapshot()`, `UpdateSchoolSettings`, the revisions migration, settings tests.
- **Dependencies:** MySQL/SQLite date handling; migration policy.
- **Verification strategy:** feature test asserting a second update succeeds and the revision keeps `effective_from = null`; run against SQLite and MySQL strict mode (MySQL run not performed in this audit).

#### B07-04 — `academic_periods` lacks a composite tenant foreign key (P1, VERIFIED gap; PROPOSED risk)

- **Evidence:** `2026_11_27_000007_create_academic_periods_table.php:15-16` creates independent FKs for `school_id` and `academic_year_id`; contrast the composite pattern in `2026_11_28_000005_create_class_offerings_table.php:23-32` and the existing `academic_years(school_id,id)` unique key in `2026_11_28_000000...:18`. The application guard is `GuardsCalendarIntegrity.php:23-35` and the factory compensates at `AcademicPeriodFactory.php:40-44`. PR #63 documents this as an unresolved decision.
- **Risk:** a period can carry `school_id=A` while its year belongs to `school_id=B`; tenant-scoped reads and parent ownership then disagree.
- **Impact:** raw SQL, imports or a future write path bypassing the actions can create cross-tenant corruption; cascade deletion removes a period from the wrong tenant context.
- **Recommended remediation (PROPOSED):** add a migration with `foreign(['school_id','academic_year_id'])->references(['school_id','id'])->on('academic_years')->cascadeOnDelete()` after validating existing rows.
- **Affected files/components:** `academic_periods`, `AcademicPeriod`, calendar write actions, `AcademicPeriodFactory`.
- **Dependencies:** existing composite unique key; data-cleanup policy; B-04 unresolved decision record.
- **Verification strategy:** mirror `AcademicStructureSchemaTest.php:46-121` — raw insert with a foreign-year ID must raise `QueryException`.

#### B07-05 — Disabled/suspended accounts retain permissions with an existing session (P1, VERIFIED baseline)

- **Evidence:** `AttemptLogin.php:46-48` checks `$user->canAuthenticate()` at login; `AccountStatus.php:14-23` defines `active`/`suspended`/`disabled`; but baseline `SchoolPermissions.php:19-30` checks only permission existence, school membership and active role assignment — no account-status check. `EnsurePermission.php:22-40` and `ResolveSchoolContext.php:27-51` also rely on `auth` only. Open PR #63 states it makes `SchoolPermissions` "fail closed for suspended or disabled accounts with an existing session".
- **Risk:** deactivation does not revoke access for an already authenticated session.
- **Impact:** a suspended/disabled staff account can continue to read and write tenant data until the session expires.
- **Recommended remediation (PROPOSED):** add the status check in `SchoolPermissions` (single source of truth) as proposed in PR #63; do not duplicate it in every controller. Decide whether suspension should also invalidate existing sessions.
- **Affected files/components:** `app/Domains/Authorization/Services/SchoolPermissions.php`, `AccountStatus`, auth/session handling, permission tests.
- **Dependencies:** PR #63 (open, unmerged) already proposes this fix; owner decision on session invalidation.
- **Verification strategy:** test that a disabled/suspended user with an authenticated session gets 403 on permission-gated routes and no permission from `effectivePermissions()`; keep login regression tests for `canAuthenticate()`.

#### B07-06 — Declared permissions are unenforced and no audit-read permission exists (P1, VERIFIED; UNRESOLVED policy decision)

- **Evidence:** `config/authorization.php:19-21` declares `school.view`, `school_identifiers.view`, `academic_calendar.view`; repository-wide search found them only in config/role definitions. `routes/academic-calendar.php:7-8,14,21`, `routes/web.php:39-41` and all settings routes lack `EnsurePermission`; `config/frontend.php:110` lists an audit-logs navigation item while `QueryAuditEvents.php:15-17` withholds the HTTP surface.
- **Risk:** configuration overstates the enforcement boundary; reviewers may believe reads are gated when they are membership-only.
- **Impact:** any member can read school profile, identifiers, calendar validation and settings; no path exists to grant/revoke audit-log viewing.
- **Recommended remediation (PROPOSED):** owner decision on intended policy, then either apply `EnsurePermission` to the corresponding routes or remove unused catalogue entries and document membership-only reads; define `school_settings.*` (B07-01) and `audit.view` (B07-27) if those surfaces are to be gated.
- **Affected files/components:** `config/authorization.php`, `routes/academic-calendar.php`, `routes/web.php`, `routes/school-settings.php`, permission tests.
- **Dependencies:** B-04 unresolved decisions F03/F04/F05; PR #63.
- **Verification strategy:** route × role matrix test enumerating all API routes and configured roles with expected statuses; assert every configured permission has at least one enforcement point.

#### B07-07 — MySQL-specific behaviour is not covered by CI (P1, VERIFIED gap; PROPOSED gate)

- **Evidence:** `phpunit.xml:27-29` forces SQLite `:memory:`; `.github/workflows/ci.yml` has no MySQL job; `docs/testing/strategy.md:35-37` and ADR-0002 record the known gap; locks (`GuardsCalendarIntegrity.php:37-45`, `GuardsAcademicStructure.php:28-31`) and case-insensitive uniqueness (B07-13) are explicitly driver-sensitive. MySQL 8.0.46 evidence in existing docs is migration-only, not an application test run.
- **Risk:** concurrency, collation, JSON and constraint behaviour can differ in the self-hosted production database.
- **Impact:** defects may escape CI and appear only in production MySQL.
- **Recommended remediation (PROPOSED):** add a MySQL 8 CI smoke job for migrations plus critical tenant/locking/audit flows; keep SQLite for the fast suite; mark MySQL-specific scenarios explicitly.
- **Affected files/components:** `.github/workflows/ci.yml`, Docker Compose test configuration, selected `tests/Feature/Domains/**`.
- **Dependencies:** MySQL service in CI; test isolation from the SQLite suite.
- **Verification strategy:** CI job green on MySQL 8; concurrent write scenario for calendar/structure/settings; result recorded separately from SQLite runs.

#### B07-08 — Sensitive future modules lack shared-kernel decisions (P1, VERIFIED absence; PROPOSED decisions)

- **Evidence:** §12 shows no learner/guardian/staff person model, no money/currency column or code (`grep currency` finds only the exclusion comment in `config/school-settings.php:11-15`), no uniform tenant-FK convention (B07-04 vs S-04), only one backed enum (`AccountStatus.php`), no archival (B07-17) and no audit event taxonomy. `config/frontend.php:37-66` lists planned learner/admissions/guardian/enrolment and fees/billing/payments/receipts screens without implementations. Research IDs for these areas are PROPOSED/UNRESOLVED (§12, §17).
- **Risk:** each future module will invent its own person, money, ownership and audit conventions.
- **Impact:** learner PII, guardian links and financial records become inconsistent and hard to secure/report; redesign cost grows with each module.
- **Recommended remediation (PROPOSED):** before implementing any of those modules, record decisions in a new ADR/decision log for (1) person/identity model, (2) money and currency representation, (3) tenant-composite-FK convention, (4) closed-vocabulary strategy, (5) archival/delete semantics and (6) audit event taxonomy with field allow-lists. Do not pre-create speculative tables.
- **Affected files/components:** `docs/adr/`, `docs/modules/platform-foundation/decision-log.md`, all future domain designs.
- **Dependencies:** Product Owner approval; resolved research U-items; official Zimbabwe sources where compliance is claimed.
- **Verification strategy:** architecture review gate: no new sensitive module PR is accepted until the decision log references the six decisions and tests assert them.

### P2 Medium

#### B07-09 — Calendar integrity and validation rules are duplicated with divergent semantics (P2, VERIFIED)

- **Evidence:** `GuardsCalendarIntegrity.php:47-65,112-128,130-144,168-189` throws `ValidationException` for name/sequence/within-year/overlap; `ValidateAcademicCalendar.php:25-75` reimplements checks as strings; year date order is reported at `:25-27` but enforced in `CreateAcademicYear.php:28`/FormRequests; the read action uses an O(n²) pairwise scan (`:66-74`) while the guard uses SQL.
- **Risk:** the validation panel can report "valid" for data the write guard rejects; config changes must be applied twice.
- **Impact:** misleading UI, maintenance drift and avoidable CPU work for large years.
- **Recommended remediation (PROPOSED):** one policy object returning structured violations; write guard maps to field errors, read action maps to strings; replace pairwise loop with one SQL `exists` check.
- **Affected files/components:** `GuardsCalendarIntegrity`, `ValidateAcademicCalendar`, `AcademicCalendarController`, `DomainCalendarManagementGateway`, `config/academic-calendar.php:80-82`.
- **Dependencies:** Eloquent queries; config flags `require_periods_within_year`, `prevent_period_overlap`, `prevent_year_overlap`.
- **Verification strategy:** property-style test asserting validator clean iff guard accepts; benchmark with 200+ periods.

#### B07-10 — Domain models are accessed across boundaries and thin-controller rule is uneven (P2, VERIFIED)

- **Evidence:** documented rule at `docs/architecture/overview.md:25`; counterexamples: `GuardsAcademicStructure.php:83-95` imports `AcademicYear`; `AcademicStructureController.php:271-277`; `DomainCalendarManagementGateway.php:12-13,47,59-64,134-148`; `ServerFrontendDataProvider.php:8-11,66-76,99-130`; `DomainSchoolSettingsGateway.php:12,61-64`; `SchoolContextController.php:8,30-41`. Controllers also contain inline `Validator::make` and mappers (`AcademicCalendarController.php:117-134`, `AcademicStructureController.php:197-222,239-366`). `App\Models\User.php:6-7` imports domain classes while domains import `User` (`RoleAssignment.php:8`, `School.php:7`).
- **Risk:** schema/policy changes propagate outside the owning domain; new callers bypass domain checks; identity has no shared-kernel contract.
- **Impact:** weaker encapsulation, duplicate read logic and a circular dependency that will worsen with learner/staff modules.
- **Recommended remediation (PROPOSED):** publish explicit domain read actions/query classes and use them in controllers/adapters; keep Eloquent models private to their domain; document `App\Models\User` as a shared kernel or move it to `App\Domains\Identity`.
- **Affected files/components:** all listed consumers, `app/Models/User.php`, future module templates.
- **Dependencies:** B07-11 (shared serializers); owner decision on identity boundary.
- **Verification strategy:** architecture test forbidding cross-domain model imports except an explicit allow-list; assert controllers contain no `Validator::make` or direct domain-model queries.

#### B07-11 — Duplicated tenant/validation/string helpers and divergent response mappers (P2, VERIFIED)

- **Evidence:** `tenantJson()` is copied in `AcademicCalendarController.php:202-207`, `AcademicStructureController.php:371-376`, `RoleAssignmentController.php:96-101`, `SchoolSettingsController.php:84-89`; `SchoolContextController.php:55-59` sets `no-store` but `Vary: X-School-Id` only. `rejectClientSchoolId()` is copied in three controllers and inlined in `AcademicStructureController.php:200`. `nullableString` is copied four times in School actions (`CreateSchool.php:38-49`, `AddSchoolIdentifier.php:87-98`, `RecordSchoolRelationship.php:120-131`, `RecordSchoolStatus.php:68-79`). API/gateway payloads differ: calendar year payload includes `school_id` in `AcademicCalendarController.php:166` but not `DomainCalendarManagementGateway.php:153-166`; period payload omits `source_reference`/`source_published_at` in the controller but includes them in the gateway (`:184-188`).
- **Risk:** a new endpoint can omit `no-store`/`Vary` or the `school_id` prohibition; UI and API contracts drift independently.
- **Impact:** possible cross-tenant cache reuse on a forgotten endpoint, inconsistent 422s and different fields for the same resource; `docs/frontend/api-dependencies.md:5` overstates parity.
- **Recommended remediation (PROPOSED):** one tenant-response helper/middleware, one `ProhibitsClientSchoolId` FormRequest concern, one `NullableString` normaliser, and domain-owned resource/serializer classes shared by controllers and gateways; align `Vary` with all selector inputs.
- **Affected files/components:** five controllers, three gateways, `ServerFrontendDataProvider`, four School actions, API docs.
- **Dependencies:** B07-10 (read actions), B07-19 (envelope).
- **Verification strategy:** contract test comparing sorted key sets of controller JSON and gateway payloads; route-wide test asserting headers and 422 on supplied `school_id`.

#### B07-12 — `school_setting_revisions` has dual parent FKs without a composite tenant link (P2, VERIFIED)

- **Evidence:** `2026_11_27_000012_create_school_setting_revisions_table.php:15-16` constrains `school_id` and `school_setting_id` independently; `:23` adds `unique(['school_id','version'])`; `SchoolSettingRevision.php:23` makes both IDs fillable. The action writes both from one school (`UpdateSchoolSettings.php:43-50`).
- **Risk:** a revision can carry `school_id=B` while referencing a setting of school A; provenance becomes cross-tenant.
- **Impact:** revision listing scoped by school (`SchoolSetting.php:54-57`) can be bypassed by direct inserts.
- **Recommended remediation (PROPOSED):** add `unique(['school_id','id'])` on `school_settings` plus a composite FK, or drop the redundant `school_id` column and derive it from the setting; narrow `Fillable`.
- **Affected files/components:** `school_setting_revisions`, `school_settings`, `SchoolSettingRevision`, `UpdateSchoolSettings`, factory.
- **Dependencies:** migration data validation; B07-15 (fillable policy).
- **Verification strategy:** raw insert with mismatched school/setting IDs must fail; add beside `SchoolSettingsMigrationTest.php:26-51`.

#### B07-13 — Case-insensitive uniqueness is application-only and driver-dependent (P2, VERIFIED)

- **Evidence:** `GuardsCalendarIntegrity.php:57-59,101-103` uses `mb_strtolower`; `GuardsAcademicStructure.php:154-176` uses `LOWER(...)`; DB uniques are plain columns (`2026_11_27_000006...:25`, `2026_11_28_000001...:23-25`, `...000002...:29-31`, `...000003...:30-32`, `...000004...:29-31`, `...000005...:34-41`). `config/database.php:20,57,77` shows SQLite default and MySQL `utf8mb4_unicode_ci`.
- **Risk:** `"Year 1"` and `"year 1"` can coexist after raw/import writes on SQLite while actions reject them; behaviour differs by driver.
- **Impact:** inconsistent uniqueness guarantees and dirty lookups/reports.
- **Recommended remediation (PROPOSED):** store a normalised column with a unique index, or an explicit driver-supported functional/NOCASE index; document the case-sensitivity contract.
- **Affected files/components:** calendar and structure name/code columns, guards, `AcademicStructureRules`, factories.
- **Dependencies:** SQLite/MySQL portability; existing case-variant data.
- **Verification strategy:** raw insert of a lowercase duplicate must fail after remediation; current `AcademicStructureSchemaTest.php:123-152` covers exact case only.

#### B07-14 — Temporal validity and closed vocabularies are not database-enforced (P2, VERIFIED)

- **Evidence:** date columns are nullable/unconstrained in `2026_11_27_000002...:21-22`, `...000003...:17-18`, `...000004...:18-19`, `...000006...:17-18`, `...000007...:20-21`; validity is checked only in actions (`RecordSchoolStatus.php:25,50-63`, `AddSchoolIdentifier.php:27`, `RecordSchoolRelationship.php:30`). `GuardsCalendarIntegrity.php:17-19` notes Blueprint has no portable CHECK support. Vocabulary lives in config (`config/schools.php:18-55`, `config/academic-calendar.php:17-67`, `config/authorization.php:41-77`) while columns are plain strings.
- **Risk:** reversed validity intervals, overlapping statuses and out-of-catalogue values can be inserted outside actions.
- **Impact:** `School::currentStatus()` (`:57-71`) and current-year resolution can return ambiguous results; reporting inherits dirty data.
- **Recommended remediation (PROPOSED):** add portable raw-SQL CHECKs where supported or reference tables/FKs for closed vocabularies; at minimum guard `valid_to >= valid_from` in migrations.
- **Affected files/components:** school/calendar tables, `audit_events`, `role_assignments`, config catalogues.
- **Dependencies:** decision on closed vs configurable vocabularies; multi-driver support.
- **Verification strategy:** raw inserts with reversed dates and unlisted values must fail; no such schema test exists today.

#### B07-15 — Mass-assignment allow-lists are inconsistent for tenant and FK keys (P2, VERIFIED)

- **Evidence:** `school_id` is fillable in `SchoolIdentifier.php:15`, `SchoolStatus.php:15`, `SchoolRelationship.php:15`, `SchoolSettingRevision.php:23`; it is absent from `AcademicYear.php:17-20`, `AcademicPeriod.php:16-20`, all Academic Structure models and `SchoolSetting.php:24`. `AcademicPeriod.php:17` leaves `academic_year_id` fillable. `AuditEvent.php:41` and `RoleAssignment.php` use `#[Guarded(['*'])]` with trusted force-fill in their actions.
- **Risk:** a future controller passing raw validated input to `fill()`/`create()` can override tenant ownership; current controllers do not.
- **Impact:** tenant safety relies on convention rather than a uniform model-level invariant.
- **Recommended remediation (PROPOSED):** remove `school_id` and parent FK IDs from `Fillable` on all tenant-owned models; set them via resolved context in trusted actions; add a test asserting the allow-lists.
- **Affected files/components:** four School-domain models, `AcademicPeriod`, factories, trusted actions.
- **Dependencies:** factory state resolution; ASSUMPTION about framework factory behaviour (vendor unavailable in this worktree).
- **Verification strategy:** static assertion of fillable contents plus a feature test that `fill(['school_id' => $other])` cannot change ownership.

#### B07-16 — Role assignment and CLI bootstrap are not audited (P2, VERIFIED)

- **Evidence:** `AssignSchoolRole.php:32-71` and `RemoveSchoolRole` perform permission checks and school stamping but contain no `RecordAuditEvent`; `AssignRoleCommand.php:14-18,35-75` validates and stamps `source = cli` but writes no event; grep for `AuditScope|RecordAuditEvent` in `app/Domains/Authorization/**` found none. PR #66 (open) records this as an owner decision, not an implemented fix.
- **Risk:** the highest-privilege action — granting `school_admin` — has no append-only record or operator attribution.
- **Impact:** incomplete forensic trail for privilege changes; no way to answer "who granted this role?" from audit events.
- **Recommended remediation (PROPOSED):** after owner approval, record events with school scope, `system`/`job` actor, `source=cli` and allow-listed command metadata; audit domain grants/removals with actor attribution.
- **Affected files/components:** `AssignSchoolRole`, `RemoveSchoolRole`, `AssignRoleCommand`, `config/audit.php`, audit tests.
- **Dependencies:** B-06 owner decisions Q3/Q4; `AuditEventRecorder`; actor/source vocabularies.
- **Verification strategy:** test that grants/removals and CLI grants write one redacted event with matching school, action and source; rejected writes write none.

#### B07-17 — Audit immutability is application-only; deletion semantics and retention are undefined (P2, VERIFIED)

- **Evidence:** `AuditEvent.php:66-75` and `ImmutableAuditEventBuilder.php:15-60` block Eloquent updates/deletes; `2026_11_27_000020_create_audit_events_table.php:15-18` uses `restrictOnDelete()` for `school_id`; no triggers or revoked GRANTs exist in `database/`; all other school-owned tables cascades (e.g. `...000005...:15`, `...000011...:15`, `...000030...:15-16`); no retention policy exists in `config/audit.php` or docs.
- **Risk:** raw SQL/privileged DB roles can modify events; conversely a school with events cannot be hard-deleted.
- **Impact:** append-only is not a database guarantee; operational cleanup fails late with an FK error; retention/compliance posture is undefined.
- **Recommended remediation (PROPOSED):** document delete/archival policy; add DB triggers or restricted grants if true immutability is required; define a retention job and align school-delete behaviour.
- **Affected files/components:** `audit_events`, `AuditEvent`, recorder, school deletion/import flows, `docs/operations/**`.
- **Dependencies:** DB privileges; owner decision on retention (B-06 Q7, NFR-007/PF-037 PROPOSED).
- **Verification strategy:** inspect target DB triggers/GRANTs; test expected school-delete behaviour; no legal claim until an official source is approved.

#### B07-18 — Route registration is scattered, routes are mostly unnamed, and no architecture tests enforce coverage (P2, VERIFIED)

- **Evidence:** five providers register API routes (`AcademicCalendarServiceProvider.php:17-19`, `AcademicStructureServiceProvider.php:17-19`, `SchoolSettingsServiceProvider.php:17-19`, `RolesServiceProvider.php:23-25`, `AuthServiceProvider.php:30`) while `bootstrap/app.php:10-14` loads only web/console; no `->name()` in four API route files; only Academic Structure has a route-wide permission test (`AcademicStructurePermissionTest.php:143-189`). `config/authorization.php:12-14` says modules opt in.
- **Risk:** middleware stacks and permission gates drift between providers; URL changes break clients/tests silently.
- **Impact:** lower discoverability, fragile frontend contracts and easy omission of `auth`/`school.context`/permission middleware in new modules.
- **Recommended remediation (PROPOSED):** centralise the `/api` group with named routes and a single middleware definition; keep domain route files as definitions; add architecture tests asserting every state-changing route has a gate or documented action-level check.
- **Affected files/components:** five providers, six route files, `bootstrap/app.php`, tests.
- **Dependencies:** B-06/B-04 permission decisions; route cache.
- **Verification strategy:** `route:list --json` assertion that every API route is named and has expected middleware; route × role matrix test.

#### B07-19 — No error envelope, correlation propagation, idempotency or pagination (P2, VERIFIED absence)

- **Evidence:** `bootstrap/app.php:20-23` only selects JSON rendering; no `Exceptions::render`, `report()` or `Log::` calls found in `app/`. Correlation IDs are parsed/stored for audit (`RequestCorrelationResolver.php:14-39`, `config/audit.php:43-46`) but not echoed. No `Idempotency-Key` occurrences anywhere. All HTTP lists use `->get()`; the sole `paginate()` is `QueryAuditEvents.php:35` with no route (`AuditEventQuery.php:48-50` has a 1–100 bound). Livewire screens handle errors differently (`SchoolSettings.php:61-72` swallows `Throwable`; `CalendarAdministration.php:135-145,201-211,230-232`).
- **Risk:** inconsistent error bodies, unsafe retries, unbounded responses and poor diagnosability.
- **Impact:** integration fragility, duplicate side effects on retry, latency/timeouts as tenant data grows.
- **Recommended remediation (PROPOSED):** documented `{error:{code,message,correlation_id,fields}}` envelope; bounded pagination reused from `AuditEventQuery`; idempotency store keyed by `(school_id, actor_id, key)`; shared Livewire error presenter that logs unexpected failures.
- **Affected files/components:** `bootstrap/app.php`, all controllers/gateways, Livewire components, API docs, new migration for idempotency.
- **Dependencies:** B07-11 (shared response helper), audit correlation config.
- **Verification strategy:** feature tests for each error class, replayed idempotency keys, >100-row pagination with clamping and stable `meta`.

#### B07-20 — Testing architecture does not cover the riskiest execution paths (P2, VERIFIED)

- **Evidence:** 29 PHP test files: 25 Feature, 1 Unit (`tests/Unit/FoundationTest.php`), 3 support; SQLite enforced by `phpunit.xml:27-29`; `docs/testing/strategy.md:3-12` still lists only two baseline tests; no MySQL CI job; no browser suite; helpers duplicated across feature files rather than shared traits; no tests for error/rollback paths, cross-domain workflows or policy/CLI decisions (B-04/B-05 reports list unresolved areas).
- **Risk:** business rules are coupled to HTTP/framework, MySQL concurrency/collation defects escape, and cross-domain regressions are invisible.
- **Impact:** slower feedback and lower confidence in the exact areas most likely to break with new modules.
- **Recommended remediation (PROPOSED):** add focused unit tests for pure guards/validators/sanitisers; add MySQL smoke job (B07-07); add risk-based negative/rollback/tenant-switch tests; centralise fixture helpers in `tests/Support/**`; refresh `docs/testing/strategy.md`.
- **Affected files/components:** `tests/Unit/**`, `tests/Feature/Domains/**`, `tests/Support/**`, `.github/workflows/ci.yml`, `docs/testing/strategy.md`.
- **Dependencies:** B07-07; owner decisions on audit/auth events.
- **Verification strategy:** `composer test --testsuite=Unit`, MySQL smoke job, targeted negative tests; record SQLite and MySQL results separately.

#### B07-21 — Requirement IDs are not traceable to code/tests and research evidence is mostly provisional (P2, VERIFIED)

- **Evidence:** `docs/modules/platform-foundation/traceability-matrix.md:1-8,63-69` is explicitly discovery-time only; `PF-`/`NFR-` IDs do not appear in `app/` or `tests/`; `docs/research/source-register.md`, `conflicting-evidence.md` and `unresolved-questions.md` classify many sources as indexed/unfetched/mirror and leave U-01–U-19 open; `ZIMSEC` sources (`OFF-07`) were indexed/unreachable; research coverage reports no stakeholder interviews.
- **Risk:** future modules may be designed from PROPOSED candidates as if they were verified requirements.
- **Impact:** incorrect compliance claims, wasted implementation and audit noise.
- **Recommended remediation (PROPOSED):** add a lightweight trace convention (requirement IDs in module docs/test names or a generated trace table) and gate each sensitive module on primary-source fetch/stakeholder validation recorded in the decision log; never convert PROPOSED to VERIFIED without evidence.
- **Affected files/components:** `docs/research/**`, `docs/modules/**`, `tests/**`.
- **Dependencies:** Product Owner/legal review; source register updates.
- **Verification strategy:** spot-check each implemented slice's PF/NFR IDs to tests/actions; assert unresolved IDs are not marked implemented.

#### B07-22 — Membership provisioning and cross-school relationships have no exposed authorization boundary (P2, VERIFIED internal-only)

- **Evidence:** `CreateUser.php:13-18,40-56` accepts `school_ids`, validates `exists('schools','id')` and syncs memberships, but no route/controller references it; `RecordSchoolRelationship.php:14-18` states it assumes a resolved context and authorisation and must not be exposed over HTTP, and validates only the related school's existence (`:27`).
- **Risk:** a future user-management UI could pass a client-controlled `school_ids` array that grants membership anywhere; a relationship endpoint could create a cross-school link without authorising the related school.
- **Impact:** membership is the root of all tenant access, so a mistake here bypasses every downstream scope.
- **Recommended remediation (PROPOSED):** wrap membership/relationship changes in dedicated actions that check an actor permission in the target school, forbid client `school_ids` in public FormRequests, and write audit events; add a `school_relationships.manage` permission if exposed.
- **Affected files/components:** `CreateUser`, `RecordSchoolRelationship`, future user/relationship routes, `config/authorization.php`, `school_user` pivot.
- **Dependencies:** B07-08 identity decision; B07-16 audit events.
- **Verification strategy:** route enumeration test asserting no public membership endpoint; new action tests for actor permission, membership validation and audit attribution.

### P3 Low

#### B07-23 — Documentation drift misstates current architecture and testing baseline (P3, VERIFIED)

- **Evidence:** `docs/architecture/overview.md:21,25,44-46` says the UI framework decision is pending, "No domain is implemented in Sprint 0", and lists school features as out of scope, while seven domains, Livewire screens and Academic Structure are implemented; `docs/testing/strategy.md:3-12` lists only two test files while 26 test classes exist; ADR-0003 is Accepted (`:3`) but retains historical freeze language.
- **Risk:** contributors and auditors rely on stale boundaries; false gaps or false permissions are inferred.
- **Impact:** onboarding errors and audit noise.
- **Recommended remediation (PROPOSED):** refresh the overview/strategy with current Phase 1 status and links to the decision log; add an "Accepted — current status" banner to ADR-0003.
- **Affected files/components:** `docs/architecture/overview.md`, `docs/testing/strategy.md`, `docs/adr/0003-frontend-strategy.md`.
- **Dependencies:** none; do not change behaviour.
- **Verification strategy:** documentation review comparing each statement against `app/Domains/**`, `tests/**` and `docs/modules/platform-foundation/decision-log.md`.

#### B07-24 — Model contract hygiene: identifier uniqueness and typed vocabularies are unresolved/inconsistent (P3, VERIFIED design; UNRESOLVED business rule)

- **Evidence:** `2026_11_27_000002_create_school_identifiers_table.php:25` and `AddSchoolIdentifier.php:69-82` enforce global `(type, normalized_value)` uniqueness with no `school_id` and no temporal non-overlap; only `AccountStatus.php:14-23` is a backed enum while all other vocabularies are config strings; `AuditEvent.php:54-59`/`RoleAssignment.php:44-46` use `$casts` while other models use `casts()` (`AcademicPeriod.php:31-39`, `SchoolSetting.php:35-41`); docblocks are partial/absent on some models.
- **Risk:** a legitimate shared identifier may be blocked; typos are only caught by specific actions; date/timezone handling differs across models.
- **Impact:** data-quality and maintenance cost; business rule remains unconfirmed.
- **Recommended remediation (PROPOSED):** confirm identifier uniqueness with product/legal evidence; introduce backed enums or value objects for closed sets; standardise cast declarations and `@property` docblocks.
- **Affected files/components:** `school_identifiers`, `AddSchoolIdentifier`, config catalogues, all domain models.
- **Dependencies:** U-01 identifier syntax/EMIS decisions; owner approval.
- **Verification strategy:** feature test asserting intended two-school identifier behaviour; static analysis after enum/cast standardisation.

#### B07-25 — Development seeder does not produce a usable tenant context (P3, VERIFIED)

- **Evidence:** `database/seeders/DatabaseSeeder.php:6,11,16-23` uses `WithoutModelEvents` and creates one `User` only; no school, `school_user` membership or role assignment is seeded despite factories for all of them; `ResolveSchoolContext.php:37-39` would 403 for that user.
- **Risk:** a fresh local database has a login with no usable school context.
- **Impact:** poor reproducibility/onboarding for contributors and demos.
- **Recommended remediation (PROPOSED):** add a dev-only seeder creating a school, membership and `school_admin` assignment via domain actions.
- **Affected files/components:** `DatabaseSeeder`, auth/role factories, `config/frontend.php:14-20` demo policy.
- **Dependencies:** demo-data policy; no production seeding.
- **Verification strategy:** `migrate:fresh --seed`, log in, load `/app` without 403 (not run in this audit).

#### B07-26 — No browser/E2E or accessibility automation (P3, VERIFIED absence)

- **Evidence:** `docs/testing/strategy.md:21` marks browser automation as planned; no `tests/Browser/**` or Playwright configuration exists; frontend feature tests use `withoutVite()` and assert rendered HTML (`tests/Feature/Frontend/**`).
- **Risk:** JS/Alpine interaction, responsive layout and focus/contrast regressions escape the fast suite.
- **Impact:** degraded shared-device/low-bandwidth experience, which `PRODUCT.md` identifies as an operating constraint.
- **Recommended remediation (PROPOSED):** add a minimal headless smoke suite for login, school selection, navigation, calendar/settings/roles screens at mobile and desktop breakpoints with keyboard/axe checks.
- **Affected files/components:** new browser suite, `.github/workflows/ci.yml`, built assets.
- **Dependencies:** browser runner and asset build; ADR-0003 accepted.
- **Verification strategy:** headless smoke in CI; keep Pest feature tests as the fast contract layer.

#### B07-27 — Audit read capability has no HTTP surface while navigation advertises audit logs (P3, VERIFIED)

- **Evidence:** `QueryAuditEvents.php:12-18` deliberately withholds HTTP until authorisation exists and provides paginated school-scoped retrieval (`:21-35`); `config/frontend.php:110` lists an "Audit logs" item without `available`/`route`, which `:7-11` says renders an honest "not built" screen.
- **Risk:** low — deliberate deferral, well documented.
- **Impact:** auditors cannot view events in the UI; the read action is under-used.
- **Recommended remediation (PROPOSED):** add a permission-gated (`audit.view`) endpoint using the future pagination/envelope contract (B07-19), then a screen; decide retention/legal hold first (B07-17).
- **Affected files/components:** `QueryAuditEvents`, new route/controller/FormRequest, `config/authorization.php`, `config/frontend.php`.
- **Dependencies:** B07-06 permission decision; B07-17 retention decision.
- **Verification strategy:** test that only `audit.view` holders see school-scoped events and that filters/pagination work.

#### B07-28 — Tenancy edge cases: body `school_id` selector and header-dependent context teardown (P3, VERIFIED behaviour; impact PROPOSED)

- **Evidence:** `ResolveSchoolContext.php:91-117` accepts `school_id` from request input as a selector when no header is supplied (after membership checks at `:49-51`) while controllers prohibit the same field (`AcademicCalendarController.php:136-141`, `SchoolSettingsController.php:55-60`, `RoleAssignmentController.php:86-91`, `AcademicStructureController.php:200`); `:65-80` defers context clearing to termination only when `X-Livewire` is present and clears in `finally` otherwise; `:123-140` re-resolves/revalidates remembered Livewire schools.
- **Risk:** the same payload is valid at the tenancy layer and invalid at the controller layer; lifecycle semantics depend on a client-supplied header.
- **Impact:** confusing 422s and possible context bleed under non-standard runtimes; **no exploitable leak is claimed** because membership revalidation remains authoritative.
- **Recommended remediation (PROPOSED):** accept tenant selection only from `X-School-Id` plus the explicit Livewire session path; keep controller prohibition as defence in depth; make setup/teardown unconditional and idempotent on every update.
- **Affected files/components:** `ResolveSchoolContext`, four controllers, API docs, isolation tests.
- **Dependencies:** Livewire persistent middleware; long-running runtime support.
- **Verification strategy:** tests asserting body-only `school_id` never selects a tenant and that context is cleared with and without `X-Livewire`.

## 14. Recommended remediation sequence

1. **Correctness and authorization first (P1):** B07-03 (nullable revision date), B07-01 (settings permission), B07-05 (account status in `SchoolPermissions`, coordinated with PR #63), B07-04 (composite calendar FK). Each needs a regression test before or with the change.
2. **Make boundaries enforceable:** B07-02 and B07-18 (architecture tests for scope trait, route middleware/gates and cross-domain imports), then B07-06 (resolve the permission catalogue) and B07-10/B07-11 (domain read actions and shared serializers).
3. **Close persistence and audit gaps (P2):** B07-12–B07-15 (schema constraints), B07-16/B07-17 (audit events, deletion/retention policy), B07-22 (membership boundary).
4. **Stabilise the HTTP contract:** B07-19 (error envelope, pagination, idempotency) and B07-09 (single-source calendar rules), keeping frontend adapters in lockstep.
5. **Make testing reflect production:** B07-07 and B07-20 (MySQL smoke job, unit/risk-based coverage), then B07-26 (browser smoke) if the frontend surface grows.
6. **Decide the shared kernel before school modules:** B07-08 and §18; update research evidence (B07-21) and documentation (B07-23) as decisions land.
7. **Hygiene:** B07-24, B07-25, B07-27, B07-28 as low-risk follow-ups.

Do not implement any school functional module until steps 1–2 and B07-08 are approved.

## 15. Dependencies and conflicts with existing PRs

**VERIFIED** at audit time (`gh pr list`/`gh api`, 2026-11-28). `origin/main` at `9f84acb` already includes merged PRs #62 (`98e8226`), #65 (`bb79498`) and #64 (`9f84acb`). The following PRs were **open and unmerged** and are **not** assumed to be in the baseline:

| PR | Head | State observed | Relevance |
| --- | --- | --- | --- |
| #63 | `audit/b04-security-rbac-isolation` | open, `MERGEABLE` / `UNSTABLE`; all four head check-runs (`tests`, `docker`, `quality`, GitGuardian) reported success at `ba800ea` | Touches permission middleware, FormRequests, `SchoolPermissions`, routes, Livewire and tests. Directly overlaps B07-01, B07-05 and B07-06. Its report records the `academic_periods` composite FK as unresolved (B07-04). |
| #66 | `research/b06-audit-policy-decisions` | open; head check-runs success at `36ee9c3` | Documentation-only policy brief; overlaps B07-16/B07-17 owner decisions (Q1–Q7). |
| #67 | `fix/academic-structure-docs-accuracy` | open; head check-runs success at `198fb79` | Documentation-only; touches `docs/modules/academic-structure.md`. No overlap with this report. |
| #68 | `test/academic-structure-coverage-gaps` | open; head check-runs success at `b75a6b4` | Test-only; touches `tests/Feature/Domains/AcademicStructure/AcademicStructureTest.php`. Relevant to B07-20 verification. |
| #69 | `feature/frontend-academic-structure-ui` | open, `MERGEABLE` / `CLEAN`; head check-runs success at `0e801c6` | Adds frontend gateway/screen; overlaps `routes/web.php` and `AppServiceProvider.php` with #63. Relevant to B07-10/B07-11 contract work. |

**Post-audit observation (VERIFIED after the baseline was fixed, 2026-11-28):** `origin/main` subsequently advanced to `6f2d868` — "fix(auth): close authorization and tenant isolation gaps (#63)" — so PR #63 has since merged. Every finding in this report remains scoped to baseline `9f84acbfae05b95fe4e96f828865986441d1de21` and was **not** re-validated against `6f2d868`. Readers should reconcile B07-01, B07-05 and B07-06 against the merged implementation before acting on them. This report adds only a new file and does not conflict with that merge.

**Observed file overlap between open PRs:** `docs/README.md` (#63 and #66) and `routes/web.php` (#63 and #69). This report adds only `docs/reports/b07-domain-architecture-audit.md`, so it conflicts with none of them. Local-only branches `audit/b04-authorization-tenant-isolation` (no remote) and `feature/backend-learner-management-foundation` (at `98e8226`, no learner code yet, no remote PR) were left untouched.

**Conflicts deliberately avoided:** no change to RBAC (`config/authorization.php`, `SchoolPermissions`, permission tests), no change to audit implementation (`app/Domains/AuditTrail/**`, `config/audit.php`), no change to routes, controllers, actions, migrations or tests, and no modification of `docs/README.md` (owned by open PRs). Recommendations that touch those areas are documented as findings, not implemented.

**Dependencies:** B07-01/B07-05/B07-06 depend on the owner decisions and implementation path chosen in #63; B07-16/B07-17 depend on #66 decisions; B07-07 depends on CI configuration; B07-08/B07-21 depend on research/legal validation; B07-04 depends on a data-cleanup policy for existing rows.

## 16. Proposed architecture improvements

- **Domain read API:** one public action/query per supported read, returning domain DTOs; controllers and `app/Frontend` adapters consume them, making `docs/architecture/overview.md:25` enforceable rather than aspirational (B07-10).
- **Shared serializers/Resources:** one mapping per resource used by HTTP and frontend adapters, with UI-only flags explicitly named (B07-11).
- **One tenant HTTP cross-cutting layer:** named `api.tenant` group with single middleware definition, shared response headers, FormRequest concern, error envelope, bounded pagination and optional idempotency (B07-18, B07-19).
- **Policy-as-config with enforced coverage:** either gate every declared permission or remove unused entries; a test fails if a configured permission has no enforcement point (B07-06).
- **Persistence invariants:** uniform composite tenant FKs, normalised unique keys, portable CHECKs/reference tables where supported, and a documented archival policy (B07-04, B07-12–B07-15, B07-17).
- **Architecture tests:** codify the documented rules — no cross-domain model imports outside an allow-list, no `Validator::make` in controllers, all API routes named, all tenant reads scoped, headers on all tenant responses (B07-02, B07-10, B07-18).
- **Shared-kernel ADR:** person/identity, money/currency, tenant key convention, vocabularies, archival and audit taxonomy decided **before** learner/guardian/finance modules (B07-08).

These are proposals only; none was implemented in this audit.

## 17. Requirements traceability where evidence exists

| Requirement / decision evidence | Classification | Implementation evidence |
| --- | --- | --- |
| ADR-0001 stack/modular architecture; `docs/architecture/overview.md:5,13-25` | VERIFIED decision | 7 `app/Domains` contexts, 32 actions, thin HTTP layer with exceptions in B07-10. |
| ADR-0002 MySQL system of record, SQLite tests | VERIFIED decision | `phpunit.xml:27-29`, Docker Compose MySQL; MySQL application behaviour untested (B07-07). |
| ADR-0003 frontend strategy | VERIFIED accepted 2026-11-27 | Blade/Livewire shell and screens; historical freeze language remains (B07-23). |
| School isolation / reversible shared-DB boundary (`docs/architecture/overview.md:38-42`, decision log D-04) | VERIFIED provisional | `ResolveSchoolContext`, `SchoolContext`, scopes; no final topology ADR. |
| Authentication rule "login does not accept a school identifier" (`docs/architecture/overview.md:34-36`) | VERIFIED | `routes/auth.php:10-20`, `LoginController.php:17-38`, `AttemptLogin.php`. |
| Current-year rule (`config/academic-calendar.php:28-32`, labelled PROPOSED) | PROPOSED rule, VERIFIED implementation | `ResolveCurrentAcademicYear.php:11-43`. |
| Calendar integrity flags (`config/academic-calendar.php:69-82`, provisional engineering safeguards) | PROPOSED rule, VERIFIED implementation | `GuardsCalendarIntegrity.php:47-189`; duplication in B07-09. |
| Structural vocabulary examples (`config/academic-structure.php:9-16`) | PROPOSED, no universal model asserted | Levels/grades/classes/streams/offerings tables and actions. |
| Settings catalogue (issue #38, U-13 unresolved; `config/school-settings.php:6-15`) | VERIFIED mechanism, PROPOSED catalogue | `locale`/`timezone` only; finance/currency explicitly excluded. |
| Identifier syntax unverified (`config/schools.php:23-31`) | UNRESOLVED | Patterns null; global uniqueness enforced (B07-24). |
| Audit trail foundation (decision log slice 7; B-05 report) | VERIFIED partial | Append-only model/recorder; auth/role/CLI events unresolved (B07-16). |
| Frontend API dependency tracker (`docs/frontend/api-dependencies.md:3-5`) | VERIFIED intent, imprecise in places | B07-11 shows two response fields where shape parity is claimed. |
| PF/NFR research IDs (`docs/research/**`) | Mostly PROPOSED / UNRESOLVED | No `PF-`/`NFR-` references in `app/` or `tests/` (B07-21). |

## 18. Unresolved decisions

- **Settings catalogue / currency (U-13; `config/school-settings.php:11-15`):** no currency, terminology, attendance, finance or module settings without evidence or owner approval.
- **Identifier syntax and school categories (U-01 etc.; `config/schools.php:23-31`):** syntax remains unverified; global uniqueness may be wrong (B07-24).
- **Calendar workflow/counting conflicts (WF-02, C-07; `config/academic-calendar.php:12-14,50-53`):** review-before-publication and school-days vs calendar-days are configurable, not asserted.
- **Academic structure vocabulary (`config/academic-structure.php:9-16`):** examples only; no universal Zimbabwean structure is claimed.
- **Tenancy topology (D-04):** shared-database boundary is reversible; final topology requires a new ADR.
- **Offline/resilience targets (D-05/U-14/U-15) and stakeholder validation (D-06):** no numeric targets, no interviews claimed.
- **Branch protection (D-07, issue #32):** no governance change relied on.
- **B-04 unresolved items F03/F04/F05/F07:** view permission enforcement, settings permissions, manage-link visibility and signed tenant file routes; PR #63 documents them rather than deciding them.
- **B-06 owner questions Q1–Q7 (PR #66):** event taxonomy, denied-attempt events, CLI actor semantics, explicit operator ID, `roles:assign` docblock, system-scope read path, retention.
- **This audit's new decisions:** account-status revocation semantics (B07-05); global tenant scope vs architecture-test-only enforcement (B07-02); person/identity, money/currency, tenant-FK, vocabulary, archival and audit-taxonomy kernel (B07-08); audit visibility and `audit.view` (B07-06/B07-27); body `school_id` selector removal (B07-28).

No compliance, legal or ZIMSEC requirement is asserted in this report; official sources cited in `docs/research/source-register.md` are repository evidence with documented reliability caveats, not verified business rules.

## Verification performed for this report

- `git fetch origin --prune` then `git rev-parse origin/main` → baseline `9f84acb…`; `git worktree add` created the isolated worktree on `feature/backend-domain-architecture-audit`.
- `gh pr list`/`gh pr view`/`gh api` inspected all five open PRs and their head check-runs; results recorded in §15. Remote CI results are limited to the observed check-run conclusions at the recorded head SHAs.
- Static repository inspection of `app/`, `routes/`, `config/`, `database/`, `tests/`, `docs/` and `.github/`; no application code was changed.
- A `php artisan route:list` attempt in this worktree failed because `vendor/autoload.php` is absent; runtime route counts are therefore **not** claimed in this report. Route facts come from static route files and the existing B-04 route inventory.
- `git diff --check`, a documentation structural check and final `git status` were run before commit; results are recorded in the commit/PR evidence, not as application test results.
- **Not run:** `make verify`, Pest, Pint, PHPStan/Larastan, migrations, MySQL checks. This task modifies only documentation, so no application test result is claimed.
