# Phase 1 decision log

**Status:** implementation decisions recorded during Phase 1 execution.
**Gate source:** `docs/modules/platform-foundation/phase-1-specification-proposed.md` (`D-01`–`D-08`) and issue `P1-G01` (#31).
**Approval basis:** the Product Owner's direct execution instruction to implement the researched Phase 1 foundation using existing evidence. This log limits implementation to the evidence-backed foundation and records uncertainty rather than converting it into confirmed requirements.

The proposed specification and research documents were merged into `main` as PR #33 (`f00018d`), so the cross-links in this log resolve against in-repo documents.

## Implementation status

| Slice | Issue | PR | Status |
| --- | --- | --- | --- |
| 1 — School identity | #34 | #41 | Merged |
| 2 — School isolation | #35 | #43 | Merged |
| 3 — Authentication/users | #36 | #44 | Merged |
| 4 — Roles/permissions | #37 | — | Not started |
| 5 — School configuration | #38 | — | Not started |
| 6 — Academic calendar | #39 | #45 | Merged |
| 7 — Audit trail | #40 | — | Not started |

## Decisions

| ID | Status | Decision | Rationale / consequence |
| --- | --- | --- | --- |
| D-01 | **Approved with exclusions** | The evidence-backed candidate set is used as the Phase 1 foundation. The exclusions in section 6 of the proposed specification remain in force. | Implementation proceeds without admissions, learner management, attendance, examinations, finance, payroll, boarding, transport, library, procurement, UI or dashboards. |
| D-02 | **Provisional** | Phase 1 implementation is limited to the seven foundation slices: school identity, school isolation, authentication/users, roles/permissions, school configuration, academic calendar and audit trail. | Identifier syntax, tenancy topology, code/class formats and numeric resilience targets remain unresolved and must not be asserted as confirmed requirements. |
| D-03 | **Deferred** | Controller/DPO licensing metadata for public schools is not implemented. | U-01 is unresolved; this requires legal clarification, not an engineering guess. |
| D-04 | **Provisional** | Use the existing single application database with server-side school scoping. Open an ADR before choosing a final tenancy topology. | The evidence does not mandate separate databases or schemas. A reversible shared-database boundary can be enforced in domain code and tested now. |
| D-05 | **Deferred** | Do not set numeric offline-operation or resilience targets. | U-14/U-15 are unresolved. The current Compose stack remains development-only under ADR-0004. |
| D-06 | **Deferred** | Do not claim stakeholder-interview validation. | No interviews were conducted; research evidence remains classified as documented/official/legal/proposed only. |
| D-07 | **Unresolved** | No branch-protection governance change is relied upon by implementation. | `P1-G02` (#32) remains open. Required approvals stay as currently configured; CI remains mandatory. |
| D-08 | **Recorded / superseded** | The no-implementation rule was respected through discovery. The Product Owner's later direct implementation instruction is treated as the gate release for this execution phase. | Future gates should be recorded on `P1-G01`; the GitHub issue comment was not used as the sole authority for this decision. |

## Standing constraints

- No new framework, module package, microservice or UI framework.
- All tenancy and permission decisions are enforced server-side; client-supplied school IDs are never trusted as tenant context.
- Identifier formats, school categories and calendars are configurable rather than encoded as universal assumptions.
- Security-critical, data-integrity, irreversible or spec-changing decisions remain with the Product Owner.
- Every implementation slice is verified with Pest, Pint, PHPStan/Larastan, Composer audit and Docker checks, then left open for human review.

## Known limitations to resolve before exposing domain actions over HTTP

- School relationship actions validate that the related school exists. Caller authorization and role/permission enforcement (Slice 4) are not yet in place, so these actions remain internal.
- Identifier values are unique case-insensitively after trimming. The syntax itself remains unverified and configurable.
- Status periods cannot overlap for a school. `currentStatus()` resolves ties deterministically by effective date, creation time and ID.

## Slice 2: implemented school isolation

- `school_user` is the only source of school membership. `ResolveSchoolContext` resolves the active school from the authenticated user's memberships; a client-supplied school ID can only select among those memberships and never grants access to another school.
- `SchoolContext` is request-scoped, registered as a singleton, populated only by the middleware and cleared after each request. Queries using `forCurrentSchool()` fail closed when no school has been authorised.
- `forSchool()` is reserved for trusted server-side callers that have already resolved a school. School-owned models opt into the scope explicitly rather than through a global scope.
- This is the provisional shared-database design recorded in D-04. A final tenancy topology ADR is still required; no topology decision is treated as settled by this implementation.
- Permissions are not part of this slice. The two JSON endpoints use the existing `auth` middleware plus school context; role and permission enforcement is deferred to Slice 4.

## Slice 6: implemented academic calendar

- `config/academic-calendar.php` holds statuses, period types, counting bases and provisional integrity switches. No dates, term count, term name or universal Zimbabwean calendar is encoded.
- Years and periods are school-owned and use explicit `forCurrentSchool()` reads. Client-supplied `school_id` is never accepted as tenant context; cross-school records are not visible over HTTP and are rejected by domain actions.
- A source is required on writes. Reference and publication date are optional because the evidence supports provenance but not a single reference format.
- Period boundaries and overlap protection are enabled by default. Year overlap is configurable and off by default because it is an engineering safeguard, not an evidence-backed requirement.
- Year updates must keep existing periods inside the year. Calendar writes lock the school row inside the transaction to serialise concurrent integrity checks; database unique constraints remain the final guard.
- Year references on period writes are scoped to the current school so ownership cannot be probed through validation errors. Tenant JSON responses vary on both tenant selection inputs.
- Review before publication, auditable revisions, permissions and examination windows are deferred; see [academic-calendar.md](academic-calendar.md) for the full rules, interfaces and implementation decisions.

