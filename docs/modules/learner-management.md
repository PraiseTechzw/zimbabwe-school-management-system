# Learner Management foundation (PROPOSED specification)

**Status:** Implemented and locally verified specification for an isolated backend foundation; see [verification evidence](../../reports/learner-management-verification.md). Every product-facing rule below remains **PROPOSED DESIGN** until a Product Owner decision is recorded. This branch is not merged, not deployed and not user-facing.
**Evidence basis:** [Findings 07 — learner records](../../research/findings/07-learner-records.md) (source IDs `LRN-S01`–`LRN-S09`, findings `LRN-F1`–`LRN-F7`), [evidence classification](../../research/evidence-classification.md) and the [module specification template](../../templates/module-specification.md).
**Not included:** admissions, enrolment, guardians/next of kin, medical, attendance, assessment, discipline, safeguarding, transport, boarding, finance, notifications, document storage, data-subject requests, deletion/export workflows or a frontend.

## Purpose and non-goals

This bounded context owns a **school-owned learner identity record**. It exists so a later, separately approved feature can attach enrolment, attendance or assessment without inventing a learner schema in those features. A record has a server-generated internal reference, a name, an optional date of birth, a server-controlled record status and an optional link to a school-owned Academic Structure class offering.

The following are explicitly rejected for this slice:

- no national, statutory, EMIS or ZIMSEC identifier is asserted (LRN-F3; U-07 remains open);
- no mandatory field list is claimed to come from Zimbabwean law (LRN-F1);
- no age band, grade/form mapping, eligibility rule or school practice is encoded (LRN-F2, LRN-F5);
- no application-to-learner conversion, because admissions work is separate and unmerged (LRN-F6);
- no second implementation of school context, permissions, audit or Academic Structure (LRN-F7).

"Status" in this module means **record status only**. It is not enrolment status, attendance status or progression. The MVP has exactly one server-set value, `registered`; transitions, transfers, completion and exit are deferred under LM-U-08.

## Requirement candidates

All requirements are **PROPOSED** and derive from the findings linked above.

| ID | Requirement | Source / finding |
| --- | --- | --- |
| LM-FR-01 | A school may register a learner record containing only the fields in this specification. | LRN-F1, LRN-F4 |
| LM-FR-02 | The server issues an internal reference; no client-supplied identifier is accepted. | LRN-F3 |
| LM-FR-03 | Every read and write is scoped to the server-resolved school; client `school_id` is rejected. | LRN-F7 |
| LM-FR-04 | A learner may optionally reference one owned, active class offering as an indicative placement link, with no enrolment semantics. | LRN-F5, LRN-F7 |
| LM-FR-05 | Authorised users can list, search, paginate, retrieve and correct learner records. | LRN-F1, LRN-F4 |
| LM-FR-06 | Record status is server-controlled; no transition endpoint exists in this slice. | LM-U-08 |
| LM-FR-07 | Writes append privacy-minimised, school-scoped audit events in the same transaction. | LRN-F4, LRN-F7 |
| LM-NFR-01 | No personally identifying value, date of birth or internal reference is copied into audit snapshots. | LRN-F4 |
| LM-NFR-02 | Learner responses are non-cacheable and tenant-varying. | LRN-F4, LRN-F7 |
| LM-NFR-03 | Validation runs again in domain actions so non-HTTP callers cannot bypass form rules. | LRN-F7 |
| LM-NFR-04 | Retention, deletion, portability, consent, DPIA and cross-border transfer controls are not implemented or promised. | LRN-F4, LM-U-04/05/06/10 |

## Data ownership and model

The `learners` table is owned by this context. Other contexts must not write it directly. Academic Structure tables remain owned by Academic Structure; this module only reads an owned offering as an optional placement reference.

| Column | Type | Origin | Rules |
| --- | --- | --- | --- |
| `id` | bigint, primary key | database | Internal only. |
| `school_id` | bigint, FK `schools.id` | server-resolved `School` | Cascade delete. Never accepted from the client. |
| `learner_reference` | string(32), unique | server generated | `LRN-` followed by an uppercase ULID. Not national, statutory or exam-compatible. |
| `full_name` | string(255) | client | Required, trimmed, not blank. No name-part convention is assumed. |
| `date_of_birth` | date, nullable | client | Optional `Y-m-d`; no future date. No age rule is inferred. |
| `status` | string(32), default `registered` | server | Record status only; not client-writable and not an enrolment state. |
| `class_offering_id` | bigint, nullable | client | Optional owned Academic Structure offering; composite FK includes `school_id`; deletion of a referenced offering is restricted. |
| `created_at`, `updated_at` | timestamps | database | Standard Laravel timestamps. |

Constraints and indexes:

- global unique index on `learner_reference`;
- composite index `(school_id, id)` for school-scoped lookup and listing;
- composite index `(school_id, class_offering_id)`;
- `learners(school_id, class_offering_id)` → `class_offerings(school_id, id)` with `restrictOnDelete`. A referenced offering cannot be deleted while a learner still points to it; clear the placement explicitly first. This avoids a composite `nullOnDelete` setting `school_id` to null on engines where the whole composite key is nullable. This is a PROPOSED integrity choice; no delete endpoint is exposed by either module; LM-U-11 remains open;
- no unique constraint on `full_name` or `date_of_birth`: different learners may share either, and no source establishes a uniqueness rule.

No migration backfill is required. The table is new and the branch adds no seeder.

## Workflows

### WF-LM-01 — Register a learner

1. The authenticated user selects one authorised school through the existing `school.context` middleware.
2. `EnsurePermission:learners.manage` resolves the actor and school server-side.
3. `StoreLearnerRequest` rejects `school_id`, `learner_reference`, `status` and timestamp fields, then validates the writable fields.
4. `RegisterLearner` validates the same data again, opens a transaction, locks the school row, resolves any `class_offering_id` through the resolved school, generates the reference, stamps `school_id`, stores the record with status `registered`, and appends a `learner.registered` audit event inside the transaction.
5. The controller returns the learner payload with `201`.

### WF-LM-02 — List and search learners

1. `EnsurePermission:learners.view` gates the request.
2. `search` is optional and trimmed; an empty value is treated as no filter. It matches `full_name` or `learner_reference` case-insensitively. SQL wildcard characters in the search term are escaped, so a user cannot widen the query with `%` or `_`.
3. `per_page` defaults to 25 and is bounded to `1..100`; `page` must be at least 1.
4. Results are scoped with `forCurrentSchool()`, ordered by `id` descending, paginated and mapped to the same learner payload used by reads.
5. No status, offering or date filter is added in the MVP because no policy for one is approved.

### WF-LM-03 — Retrieve a learner

A numeric route ID is resolved with `forCurrentSchool()->findOrFail()`. A foreign or missing ID returns the generic `404` JSON response; the response differs neither in status nor in message for those two cases.

### WF-LM-04 — Correct a learner record

1. `EnsurePermission:learners.manage` gates the request.
2. The target is school-scoped before the domain action is called.
3. `UpdateLearnerRequest` accepts only `full_name`, `date_of_birth` and `class_offering_id`. Omitted fields are unchanged; an explicit `null` date or offering clears it. `school_id`, `learner_reference` and `status` remain prohibited.
4. `UpdateLearner` revalidates, locks the school row, re-resolves the target and any offering against the resolved school, records a before snapshot, saves, and appends `learner.updated` in the same transaction.
5. The controller returns `200` with the updated payload. No update can change ownership, reference or status.

## Public interface

- **Actions:** `RegisterLearner::execute(School $school, array $data): Learner`; `UpdateLearner::execute(School $school, Learner $learner, array $data): Learner`.
- **Read models:** `Learner::query()->forCurrentSchool()` and the JSON payload builder in `LearnerController`. No global scope is registered.
- **Audit events emitted:** `learner.registered`, `learner.updated` in category `learner_record`.
- **Events consumed:** none.
- **HTTP contract:** see [Learner Management API](../../api/learner-management.md).

## Permissions and isolation

Two permissions are added to the existing catalogue:

| Permission | Granted in MVP | Meaning |
| --- | --- | --- |
| `learners.view` | `school_admin` via `*` only | List and retrieve learner records. |
| `learners.manage` | `school_admin` via `*` only | Register and correct learner records. |

No explicit grant is added for `school_member`, `school_editor` or `role_viewer`. LM-U-07 remains open: staff access to learner personal information is a product/privacy decision, not an engineering default. Wildcard `school_admin` automatically receives both new catalogue entries; this does not change any existing role behaviour.

Isolation follows the verified baseline pattern:

- `SchoolContext::assertCurrent()` is the only source of tenant ownership on writes.
- `forCurrentSchool()` fails closed when no school has been authorised.
- Client `school_id` is `prohibited` on all write bodies and on the list query.
- Cross-school route targets return `404`; cross-school offering references return a generic `422` that does not name another tenant.
- Tenant-varying responses send `Cache-Control: no-store` and `Vary: X-School-Id, school_id`.
- Domain actions repeat validation and ownership checks but rely on the route permission gate for actor authorization. Non-HTTP callers must apply their own permission check; the existing Academic Structure module has the same boundary.

## Validation

Shared rules live in `app/Domains/LearnerManagement/Support/LearnerRules.php` and are used by both FormRequests and actions.

| Field | Create | Update | Additional domain check |
| --- | --- | --- | --- |
| `full_name` | required string, max 255, not blank | sometimes, same rules | trimmed before storage |
| `date_of_birth` | nullable, `Y-m-d`, not in future | sometimes, nullable, same rules | none |
| `class_offering_id` | nullable integer, min 1 | sometimes, nullable integer, min 1 | owned offering, active when `learner-management.require_active_placement` is true |
| `school_id` | prohibited | prohibited | never read from input |
| `learner_reference`, `status`, timestamps | prohibited | prohibited | server-owned |
| `search` (list query) | optional string, max 120 | n/a | trimmed; empty becomes no filter |
| `per_page` (list query) | optional integer `1..learner-management.max_per_page` | n/a | default from config |
| `page` (list query) | optional integer, min 1 | n/a | handled by Laravel paginator |

`require_active_placement` defaults to `true` and `max_per_page` to `100`. Both are PROPOSED engineering safeguards in `config/learner-management.php`, not sourced policy.

## Audit, privacy, retention and portability

- Both write actions call `RecordAuditEvent` inside their transaction with source `domain`, category `learner_record`, the server-resolved school scope and the learner ID as `resource_id`.
- Snapshots contain only `status`, `full_name_present`, `date_of_birth_present` and `class_offering_id`. Names, dates of birth and the learner reference are never copied into audit metadata or changes.
- Read requests are not audited: logging every learner lookup would create a second, more revealing record of personal data.
- Rejected validation, rejected ownership checks and failed transactions append no event.
- There is no delete, archive, export or data-subject endpoint. CDPA rights, retention, legal hold, controller/processor status, guardian verification, DPIA and cross-border transfer questions remain open (LRN-F4; LM-U-04/05/06/10). No retention period is invented.
- No security, medical, biometric, genetic, guardian, disciplinary or safeguarding data is collected here.

## Testing strategy

Feature tests cover domain invariants, HTTP behaviour, isolation, permissions, audit and schema:

| Test file | Coverage |
| --- | --- |
| `LearnerManagementTest.php` | registration, update, optional placement, trimming, validation, status/reference server ownership |
| `LearnerManagementIsolationTest.php` | authentication, school context, client `school_id`, cross-school route targets, cross-school offerings, tenant headers |
| `LearnerManagementPermissionTest.php` | forbidden roleless/other-role access, wildcard administrator access, permission on every route binding |
| `LearnerManagementAuditTest.php` | event actions, school scope, privacy-minimised snapshots, no event on rejected writes |
| `LearnerManagementSchemaTest.php` | columns, unique key, composite foreign key and index names |

The Pest suite runs on in-memory SQLite. MySQL fresh migration/rollback checks are recorded separately in the verification section and are not represented as MySQL test coverage.

## Evolution plan

1. This isolated branch records the specification, implements the schema/domain/HTTP foundation, adds regression tests, and is left open for review.
2. After a Product Owner decision, a separate change may add status transitions, enrolment or guardian data. Each requires its own research and requirement IDs; none is implied by this module.
3. Identifier syntax, retention and portability changes are reversible through configuration or additive migrations only after review. No existing learner reference is reinterpreted as a national identifier.
4. The frontend may consume the contract in `docs/api/learner-management.md` only after backend verification has been recorded; no UI is part of this slice.

## Unresolved questions

| ID | Question | Owner |
| --- | --- | --- |
| LM-U-01 | What do SI 13 of 2025 and prescribed Education regulations require in an enrolment register? | Product Owner / legal review |
| LM-U-02 | Do MoPSE ED 46 / EMIS prescribe learner-level fields or identifiers? | Product Owner / MoPSE guidance |
| LM-U-03 | Is any mandated national learner identifier in use, and may systems store ZIMSEC candidate numbers? | Product Owner / legal review |
| LM-U-04 | Who is controller/processor for a deployment, and what DPO/licence duties apply? | Product Owner / legal review |
| LM-U-05 | What lawful basis and guardian-verification process apply to routine school records? | Product Owner / legal review |
| LM-U-06 | What retention/deletion schedule and legal-hold process apply? | Product Owner / legal review |
| LM-U-07 | Which roles may view or manage learner records; should `school_member` have any access? | Product Owner |
| LM-U-08 | What status vocabulary and transitions are valid? | Product Owner |
| LM-U-09 | Should external EMIS/ZIMSEC identifiers be stored, and under what provenance rules? | Product Owner |
| LM-U-10 | What portability/export format is required, and to whom may it be given? | Product Owner / legal review |
| LM-U-11 | When an Academic Structure offering is deleted or retired while learners reference it, should placement be cleared, blocked or migrated? | Product Owner + Academic Structure owner |

## Traceability

| Source | Finding | Requirement | Design | Implementation | Test |
| --- | --- | --- | --- | --- | --- |
| LRN-S01 | LRN-F1 | LM-FR-01 | minimal record, no mandatory field claim | `learners` migration, `RegisterLearner` | `LearnerManagementTest`, schema test |
| LRN-S01 | LRN-F2 | LM-FR-01 | optional DOB, no age rule | nullable `date_of_birth` | `LearnerManagementTest` |
| LRN-S07, LRN-S09 | LRN-F3 | LM-FR-02 | server ULID reference | `learner_reference` generation and prohibited input | `LearnerManagementTest`, isolation test |
| LRN-S02–S05 | LRN-F4 | LM-FR-07, LM-NFR-01–04 | privacy-minimised audit, no delete/export | audit concern and response headers | audit test, isolation test |
| LRN-S06, LRN-S09 | LRN-F5 | LM-FR-04 | optional owned offering placement | composite FK, guarded resolve | domain and isolation tests |
| in-repo admissions evidence | LRN-F6 | non-goal | no conversion path | no admissions code touched | route/schema tests |
| baseline architecture | LRN-F7 | LM-FR-03, LM-NFR-02/03 | reuse context/RBAC/audit | provider, FormRequests, actions | isolation/permission/audit tests |
