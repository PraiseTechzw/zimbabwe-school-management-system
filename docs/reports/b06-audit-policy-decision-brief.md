# B-06 — Audit policy decision brief (PROPOSED — awaiting owner approval)

**Status:** decision preparation only. No policy is accepted, no code or configuration is changed, and no role-change or CLI audit event is implemented by this task.
**Baseline:** `origin/main` `6844b4289af6c5446b80d897f73de39d4bb6aa7b` (merge of PR #61), verified by `git fetch --prune` and `git rev-parse`.
**Open, unmerged PRs:** #63 `audit/b04-security-rbac-isolation` at `7567ebc7bc09aaee3fb25de413566626b0fb9510`; #65 `audit/b05-audit-trail-coverage` at `8fbda4db2f852797d3bf19d474795db13687638b`. Both report `mergedAt: null`. Nothing below assumes either is merged.
**Approval status:** both proposals require Product Owner / security owner approval before any implementation. The word “proposed” is used deliberately throughout.

## 1. Evidence and provenance

Verified sources in this worktree (baseline `6844b42`):

- Audit domain: `app/Domains/AuditTrail/**`, `config/audit.php`, `database/migrations/2026_11_27_000020_create_audit_events_table.php`.
- Authorization: `app/Domains/Authorization/**`, `app/Http/Controllers/RoleAssignmentController.php`, `app/Http/Requests/Authorization/StoreRoleAssignmentRequest.php`, `app/Http/Middleware/EnsurePermission.php`, `routes/roles.php`, `config/authorization.php`, `app/Providers/RolesServiceProvider.php`.
- Auth/tenant boundaries: `app/Domains/Auth/**`, `routes/auth.php`, `app/Http/Middleware/ResolveSchoolContext.php`, `app/Domains/School/Context/SchoolContext.php`, `app/Providers/AuthServiceProvider.php`.
- Tests: `tests/Feature/Domains/AuditTrail/*`, `tests/Feature/Domains/AcademicStructure/AcademicStructureAuditTest.php`, `tests/Feature/Domains/Authorization/RolesPermissionsTest.php`, `tests/Feature/Domains/Auth/AuthenticationTest.php`.
- Docs: `docs/modules/platform-foundation/audit-trail.md`, `roles-and-permissions.md`, `decision-log.md`; `docs/research/non-functional-requirements.md`, `requirement-candidates.md`, `unresolved-questions.md`.

Open-PR sources (read, not merged into this branch):

- B-04 report with D-01–D-11, risks `B04-O05`/`B04-O06`: `docs/reports/b04-authorization-rbac-tenant-audit.md` on PR #63 head (lines 176–177, 184–193).
- B-05 coverage report: `docs/reports/b05-audit-trail-coverage.md` on PR #65 head (matrix lines 119–121, reconciliation lines 145–146, remaining gaps and recommendation at lines 173–175 and 243).

Two documentation cautions:

1. The baseline decision log already uses `D-05` for offline/resilience targets and `D-06` for stakeholder-interview validation (`decision-log.md:29-30`). These are unrelated to B-04 D-05/D-06. This brief therefore uses the unambiguous IDs **`B06-D-05`** and **`B06-D-06`** and does not alter the existing rows.
2. There is **no approved legal requirement** for these events in the repository. `docs/research/non-functional-requirements.md:13` (NFR-007), `requirement-candidates.md:39` (PF-037) and `PF-027` are explicitly **PROPOSED**; `unresolved-questions.md:12` (U-04) is open and records **“No Zimbabwean law found”** on audit-log content and retention. This brief does not assert a legal obligation, retention period or compliance certification.

## 2. Verified facts common to both decisions

| Topic | Verified baseline behavior | Evidence |
| --- | --- | --- |
| Audit write path | `RecordAuditEvent::execute(AuditScope, AuditEventData)` delegates to `AuditEventRecorder::record()`, the “single supported way to append an audit event”. No `try`/`catch`: validation failures propagate. | `Actions/RecordAuditEvent.php:15-22`; `Services/AuditEventRecorder.php:20,29-67` |
| Required event data | `action`, `resourceType`, `resourceId` required; `category`, `metadata`, `changes`, `actor`, `correlationId`, `source`, `occurredAt` optional. Slugs and sizes are validated before write. | `AuditEventData.php:19-52`; `AuditEventRecorder.php:83-114` |
| Actor vocabulary | `AuditActor::user()` (positive integer id), `AuditActor::system()` → `('system','system')`, `AuditActor::job()` (named string, 1–100 chars). `config('audit.actor_types')` allows **only `user`, `system`, `job`** — there is no `cli` actor type. | `AuditActor.php:23-48`; `config/audit.php:18-22` |
| Actor fallback | `AuthenticatedActorResolver` returns `AuditActor::user($user)` when `Auth::user()` is an `App\Models\User`, otherwise `AuditActor::system()`. No client input is read. | `Support/AuthenticatedActorResolver.php:20-24` |
| Source vocabulary | `config('audit.sources')` allows `domain`, `http`, `cli`, `job`, `import`. **`cli` is a source, not an actor.** | `config/audit.php:24-30`; `AuditEventRecorder.php:97-105` |
| Tenant scope | `AuditScope::forSchool(School)` accepts only an already-resolved `School` model; `AuditScope::system()` is the only null-school path. No raw client id factory exists. `QueryAuditEvents::execute(School, ...)` cannot read null-school events, so system-scope events are write-only today. | `AuditScope.php:12-35`; `Actions/QueryAuditEvents.php:15-35` |
| Payload safety | Recursive **key-based** redaction (`[REDACTED]`), 30 metadata keys, depth 3, 25 list items, 255-char strings; no value-content scanning. Domain traits store named scalar snapshots, never request bodies or whole model arrays. | `Support/SanitizeAuditData.php:19-141`; `config/audit.php:59-71`; `RecordsCalendarAudit.php:14-83`; `RecordsAcademicStructureAudit.php:17-123` |
| Append-only | Model `updating`/`deleting` hooks throw; builder blocks `update`, increments and `delete`. Baseline builder does **not** override `upsert`/`updateOrInsert`/`truncate` (PR #65 adds those overrides). Raw SQL/privileged DB roles bypass Eloquent. | `Models/AuditEvent.php:66-83`; `ImmutableAuditEventBuilder.php:17-45` |
| Transactions | The recorder opens no transaction. Calendar/structure actions wrap the domain save **and** audit call in `DB::transaction(...)`; exceptions roll both back. | `AuditEventRecorder.php:29-67`; e.g. `CreateAcademicYear.php:39,52,54-65`; `CreateEducationalLevel.php:41,79,81-88` |
| Role writes | `AssignSchoolRole::execute()` checks manager permission, role/source allow-lists, target membership and no privilege escalation, then `forceCreate`s the assignment. `RemoveSchoolRole` hard-deletes a school-scoped assignment. **Neither uses a transaction nor emits an audit event.** | `AssignSchoolRole.php:25-105`; `RemoveSchoolRole.php:17-29`; `Models/RoleAssignment.php:31,37-46`; migration `2026_11_27_000030:13-24` |
| HTTP authority boundary | `POST`/`DELETE /api/role-assignments` require `roles.manage`; `GET` requires `roles.view`. Controller and middleware resolve actor from auth and school from `SchoolContext`; client `school_id` is rejected. | `routes/roles.php:9-17`; `RoleAssignmentController.php:20-91`; `EnsurePermission.php:22-40` |
| CLI bootstrap | `roles:assign {--school=} {--user=} {--role=}` resolves school/user with `findOrFail`, checks membership and duplicates, writes `source = 'cli'`. No authenticated actor, no actor id, no `AuditScope`, no transaction, no audit event. Its docblock says “first role assignment in a school”, but code only rejects a duplicate user/role pair. | `Console/AssignRoleCommand.php:14-19,22-73` |
| Other provisioning | Only other Artisan command is `inspire`; `DatabaseSeeder` creates a test user and assigns no role; `CreateUser` creates users and syncs memberships with no role assignment and no audit event. | `routes/console.php:6-8`; `database/seeders/DatabaseSeeder.php:9-24`; `app/Domains/Auth/Actions/CreateUser.php:25-59` |
| Wildcard risk context | `school_admin` has `['*']`; permission expansion resolves it to every configured permission. This is a separate B-04 D-04 issue and is not changed here. | `config/authorization.php:41-71`; `SchoolPermissions.php:59-72` |
| PR #63 interaction | PR #63 adds calendar/Livewire permission gates, write rechecks, stale-account fail-closed and `prohibited`→`missing` validation. It does **not** touch `AssignSchoolRole`, `RemoveSchoolRole`, `AssignRoleCommand`, audit domain, `config/audit.php` or `config/authorization.php`. | `git diff 6844b42 7567ebc` |
| Test baseline | Existing role/CLI tests assert authorization, tenant scoping and command output only. No baseline or PR #63 test asserts an audit event for role assignment, removal or `roles:assign`. | `RolesPermissionsTest.php:37-270`; `RoleAssignmentSecurityTest.php:44-164` (PR #63 only) |

## 3. Proposed decision `B06-D-05` — role-change auditing

Maps to B-04 **D-05** / risk **B04-O05**. Status: **PROPOSED — awaiting owner approval**.

- **Current verified behavior.** Assigning or removing a school role changes effective authority but writes only `role_assignments`; there is no `AuditEvent`, no transaction and no actor stored on the assignment row (`AssignSchoolRole.php:65-71`, `RemoveSchoolRole.php:28`).
- **Problem.** Authority changes are not attributable after the fact. `role_assignments` has no `updated_at`, no actor column and hard-deletes on removal (`migration 2026_11_27_000030:13-24`), so a removal leaves no record of who removed it or what was removed.
- **Proposed solution (all elements require approval).**
  - **Operations in scope:** HTTP `POST /api/role-assignments` and `DELETE /api/role-assignments/{roleAssignment}`; the CLI `roles:assign` write uses the same event taxonomy under `B06-D-06`; any later bulk or authority-changing role operation must reuse it.
  - **Event identity:** `role_assignment.created` for assignment and `role_assignment.deleted` for removal; category `authorization`; `resource_type = role_assignment`; `resource_id = assignment id` (captured before hard delete). These are lowercase slugs accepted by current validation, but the taxonomy is **not approved**.
  - **Actor:** HTTP uses `AuditActor::user($actor)` from the authenticated actor resolved by the controller/domain check; CLI uses the explicit actor from `B06-D-06`. Client input can never select an actor.
  - **Subject:** metadata contains only named scalars — `subject_type: user`, `subject_id` (integer user id), `role` (configured role slug), `source` (allow-listed assignment source), `force_created` (boolean for the current `forceCreate` path) and `channel` (`http`/`cli`). No name, email, phone number, token, request body or whole-model dump.
  - **Tenant:** `AuditScope::forSchool($school)` with the server-resolved `School` from `SchoolContext` for HTTP and the validated `School` model for CLI; no client `school_id`. Authorization/membership failures occur before any write or event.
  - **Before/after:** create → `changes.before = null`, `changes.after = {subject_id, role, source}`; removal → `changes.before = {subject_id, role, source}`, `changes.after = null`. Do **not** copy permission arrays: they are config-derived, can change under `*` (B-04 D-04) and add noise without extra accountability.
  - **Failures:** rejected writes (missing permission, cross-school target, invalid role, non-member, duplicate, insufficient manager permissions) produce **no** event, matching the documented no-event-on-rejection rule for deliberate domain writes. A separate “denied attempt” event is an alternative, not part of this proposal (see Q2).
  - **Transaction:** wrap the assignment `forceCreate`/`delete` **and** the audit append in one `DB::transaction(...)`; if the audit write throws, the authority change rolls back. Do not emit after commit.
  - **Multi-user operations:** none exist today. If a bulk operation is later added, emit one event per user/role pair with a shared `correlationId`; never store an array of subjects in one event.
- **Alternatives and tradeoffs.**
  1. *No new events (status quo):* zero implementation cost, but B04-O05 stays open and authority changes remain unauditable.
  2. *HTTP events only:* closes the API path, leaves the trusted CLI path unaudited and creates inconsistent evidence.
  3. *HTTP + CLI with one taxonomy (recommended):* complete authority-change coverage using the existing recorder, schema and allow-lists; no new migration or permission.
  4. *Automatic request logging:* rejected by existing design — the audit foundation explicitly excludes automatic/all-request capture (`audit-trail.md:17-23`).
  5. *Denied-attempt events:* useful for escalation monitoring but changes log volume and privacy profile; needs its own decision.
- **Security implications.** Improves attribution and post-change reconstruction of authority grants/revocations. It does not itself prevent privilege escalation (the manager check and B-04 D-04 wildcard are separate concerns). Eloquent append-only guards remain bypassable by raw SQL; no tamper-evidence/retention is added. Denied escalation attempts stay unrecorded under the proposed failure rule.
- **Operational implications.** Low event volume (only successful role writes). No HTTP read endpoint exists, so evidence is available only to server-side school-scoped queries; a future UI/API requires its own permission decision. Retention/handling remains open under U-04 — no period or legal claim is proposed.
- **Implementation implications (future task only).** Add an authorization-domain audit concern following `RecordsCalendarAudit`/`RecordsAcademicStructureAudit`; call it from both actions inside a new transaction; reuse `RecordAuditEvent` with `source` explicitly set; no schema, `config/audit.php` or `config/authorization.php` change is required if the proposed slugs/category are approved. PR #63’s `prohibited`→`missing` change and Livewire rechecks must be respected, not duplicated.
- **Tests required (future task).** Listed in section 6; at minimum: emitted event on assign and remove; actor attribution; server-resolved tenant; exact named metadata; no PII/raw body; cross-school and permission-denied writes produce zero events; rollback of assignment and event on forced/audit failure; duplicate/force-create flags; one-event-per-subject if bulk is ever added.
- **Owner approval required:** **Yes** — event names, category, actor semantics, failure policy and snapshot content are all undecided by D-05/B04-O05.

## 4. Proposed decision `B06-D-06` — CLI/bootstrap audit semantics

Maps to B-04 **D-06** / risk **B04-O06**. Status: **PROPOSED — awaiting owner approval**.

- **Current verified behavior.** `roles:assign` is trusted CLI-only, writes `source = 'cli'` on the assignment, and has no authenticated actor, no audit event and no “first in school” enforcement (`AssignRoleCommand.php:14-19,22-73`). The audit config allows `cli` as a **source** but not as an actor type (`config/audit.php:18-30`). When no user is authenticated the existing resolver silently yields `actor_type = system`, `actor_id = system` (`AuthenticatedActorResolver.php:20-24`).
- **Problem.** A successful bootstrap role grant cannot be attributed to an operator or a named process; the docblock describes an invariant the code does not enforce; and “CLI” is at risk of being conflated with an actor type or with a human identity.
- **Proposed solution (all elements require approval).**
  - **Scope:** `roles:assign` and any future Artisan command that creates, deletes or changes school-scoped authority, membership or identity. Out of scope: `inspire`, read-only commands, and factories/seeders (development/test data only) unless a seeder is ever used to create production authority, in which case it needs its own decision.
  - **Actor semantics:** keep `actor_type` as the *principal*, and `source` as the *origin*.
    - `user`: only a positive user id verified against `users`; for HTTP it comes from authentication, for CLI only from an explicit, validated operator option (Q4).
    - `system`: no human principal; literal actor id `system` from the existing `AuditActor::system()` factory.
    - `cli`: **not** an actor type. Use `source = 'cli'` (already allow-listed) and record the command name in metadata.
    - `bootstrap`: a scenario, not a fourth actor type. Represent it as `actor_type = system` plus metadata `{command: 'roles:assign', bootstrap: true}`; if a verified operator id is supplied, use `actor_type = user` with `source = 'cli'` instead.
    - `job`: reserve for actual queued jobs (none exist today); do not silently reuse it for interactive CLI.
  - **No authenticated user:** use `AuditActor::system()` explicitly. Never guess a user id, use the OS username silently, or default to user `1`.
  - **School context:** the command must resolve the School from its validated `--school` id via `findOrFail`, then use `AuditScope::forSchool($school)`. There is no header, session or `SchoolContext` in CLI; a missing/invalid school fails before any write or event. School-creation events before a school exists would require `AuditScope::system()`, which current queries cannot read — do not add such events without an approved read path (Q6).
  - **Operations requiring events:** successful `roles:assign` assignments (and any future CLI authority write), with event shape from `B06-D-05`, `source = 'cli'`, `channel = 'cli'`, `command = 'roles:assign'` and the actor rules above. Validation failures, unknown school/user/role, non-members and duplicates produce no event, consistent with `B06-D-05`.
  - **Docblock mismatch:** recommended resolution is to **correct the docblock** to say “trusted bootstrap path for an existing school; duplicate user/role assignments are rejected” rather than enforce a new “first assignment only” invariant. Enforcing first-only would be a behavior change (it would block re-granting a role after removal) and needs separate product approval (Q5).
  - **Deployment/provisioning implications:** scripts must continue to pass `--school`, `--user`, `--role` and may optionally pass a verified operator id; migrations and the target school must exist before the command runs; no session/auth is required; audit writes join the same transaction as the assignment. Operators must be told that CLI authority grants are recorded under a process/system identity unless an explicit operator id is supplied. No new permission, role or config entry is needed for the recommended actor model.
- **Alternatives and tradeoffs.**
  1. *Add `cli` as an actor type:* clearer in reports, but changes the actor taxonomy and requires config changes plus decisions about actor id format; not required to record origin.
  2. *Use `AuditActor::job('roles:assign')` with `source = 'cli'`:* uses existing allow-list and keeps a named process, but conflates interactive CLI with queued-job semantics.
  3. *System actor + `source = 'cli'` + `command` metadata (recommended):* truthful when no human is authenticated, needs no taxonomy change, and preserves process provenance.
  4. *Hybrid with explicit `--actor-id` (recommended as an option):* gives human attribution when an operator is known, provided the id is validated; otherwise falls back to system.
  5. *No CLI event:* leaves B04-O06 open and creates a blind spot that is easier to miss than the HTTP path.
- **Security implications.** Removes an unattributed authority-change path and makes bootstrap actions reviewable. It does not make the CLI command less trusted (it remains intentionally unavailable over HTTP), and it does not add permission checks that could break provisioning. Operator-supplied ids must be validated to avoid fabricated attribution. System-scope events are not readable through the current query action.
- **Operational implications.** Minor logging volume; no retention/export defined (U-04); provisioning documentation must explain actor labels; re-running the same command after a removal remains allowed under the recommended docblock fix.
- **Implementation implications (future task only).** Add an explicit actor option to the command with validation, a school-scoped audit concern shared with `B06-D-05`, transaction wrapping, and metadata with command name. No new actor type, migration or source value is required for the recommended option.
- **Tests required (future task).** Listed in section 6; at minimum: system actor/source `cli`/school attribution; optional operator id → `user` actor; failed validation and duplicates produce zero events; docblock behavior (re-grant after removal) unchanged; rollback with forced failure; no PII; no HTTP route for audit events.
- **Owner approval required:** **Yes** — the actor taxonomy, operator-id policy and the “first only” interpretation are unresolved in D-06/B04-O06.

## 5. Sub-questions the owner must answer explicitly

These are checklists for the approval decision, **not** decisions made by this brief.

| ID | Question | Recommended default (PROPOSED) |
| --- | --- | --- |
| Q1 | Approve `role_assignment.created` / `role_assignment.deleted` with category `authorization` and named scalar subject metadata? | Yes; no new allow-list entry required. |
| Q2 | Record denied role-change attempts as audit events? | No for now; keep no-event-on-rejection, revisit separately. |
| Q3 | Represent CLI origin as `source = cli` with a `system` actor, or add a `cli` actor type? | `system` actor + `cli` source + `command` metadata. |
| Q4 | Allow an explicit validated `--actor-id` for human attribution? | Yes, optional; never infer from OS/session. |
| Q5 | Enforce “first role assignment in a school”, or correct the docblock? | Correct the docblock; do not change behavior. |
| Q6 | Add a read path for `system()`-scope audit events? | No for B-06; avoid school-less audit events until approved. |
| Q7 | Define retention/legal hold for these events? | Out of B-06 scope; U-04 remains open, no legal claim made. |

## 6. Implementation and test plan for a future approved task (conditional)

No work below is authorised by this brief. It is the plan to use only after an owner decision is recorded.

1. Record the owner answers to Q1–Q7; update the decision log from **PROPOSED** to the owner’s actual decision — agents must not do this unilaterally.
2. Add failing regression tests first in new suites `tests/Feature/Domains/Authorization/RoleAssignmentAuditTest.php` and `RoleAssignmentCliAuditTest.php`:
   - HTTP assign emits one event with `actor_type = user`, correct `school_id`, `role_assignment.created`, named subject scalars and no PII.
   - HTTP removal emits `role_assignment.deleted` with `changes.before` populated and `changes.after = null`; assignment id is captured before deletion.
   - Manager-permission failure, cross-school target, non-member target, unknown role and duplicate assignment each leave `AuditEvent::query()->count()` unchanged.
   - Forced outer-transaction failure rolls back assignment and event together; forced audit-write failure does the same.
   - CLI success emits one school-scoped event with `actor_type = system`, `actor_id = system`, `source = cli`, `command = roles:assign`; optional `--actor-id` produces a validated `user` actor.
   - Invalid school/user/role, non-member and duplicate CLI runs emit no event; re-grant after removal still succeeds under the docblock correction.
   - Raw request bodies, names, emails and token-like keys are absent from `metadata`/`changes`; the sanitizer’s existing key-based limits still apply.
   - No new HTTP audit endpoint; `QueryAuditEvents` remains school-scoped and cannot read system-scope rows.
   - If a bulk API is added later, one event per user/role with a shared correlation id and no subject arrays.
3. Implement a shared authorization audit concern using the existing `RecordAuditEvent` / `AuditEventRecorder` / `AuditScope` contracts; wire it into `AssignSchoolRole`, `RemoveSchoolRole` and `AssignRoleCommand` inside transactions.
4. Update `docs/modules/platform-foundation/audit-trail.md`, `roles-and-permissions.md` and the decision log to reflect the approved decision only.
5. Verify with the repository’s actual gates (`make verify` via isolated Compose), focused authorization/audit suites, `git diff --check`, and (if environment allows) MySQL migration/runtime probes reported separately from SQLite test results. Do not claim remote CI or MySQL test execution that was not run.
6. Leave the resulting PR open for human review; do not merge or mark decisions approved in code.

## 7. Documentation changes in this branch

- This brief: evidence, two proposed decisions, alternatives, implications, owner checklist, conditional implementation/test plan.
- `docs/modules/platform-foundation/decision-log.md`: a new **clearly marked proposed section** with `B06-D-05` and `B06-D-06`, both labelled **PROPOSED — awaiting owner approval**. Existing `D-01`–`D-09` rows and slice notes are untouched.
- `docs/README.md`: link to this brief.

No code, configuration, migration, role, permission or audit event is added by this branch.

## 8. Limitations

- Evidence is from baseline `6844b42`; PR #63 and PR #65 are open and may change actor/permission paths when humans merge them. Their reports are treated as proposals, not merged documentation.
- No approved requirement, law or retention period is inferred. U-04 remains open; NFR-007/PF-037 remain PROPOSED.
- This is a policy brief only: nothing here should be read as implementation, acceptance, or an authorisation to add audit events.
