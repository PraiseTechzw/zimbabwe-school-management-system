# Phase 1 decision log

**Status:** implementation decisions recorded during Phase 1 execution.
**Gate source:** `docs/modules/platform-foundation/phase-1-specification-proposed.md` (`D-01`–`D-08`) and issue `P1-G01` (#31).
**Approval basis:** the Product Owner's direct execution instruction to implement the researched Phase 1 foundation using existing evidence. This log limits implementation to the evidence-backed foundation and records uncertainty rather than converting it into confirmed requirements.

The proposed specification and research documents live on branch `docs/phase-1-discovery` (PR #33) at the time of this implementation and are not yet merged into `main`. This log records decisions against that proposal; the in-repo cross-links will resolve once PR #33 is merged.

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

- School relationship actions currently validate that the related school exists but do not yet check caller authorization or school context. They remain internal until Slice 2 tenancy and Slice 4 permissions are in place.
- Identifier values are unique case-insensitively after trimming. The syntax itself remains unverified and configurable.
- Status periods cannot overlap for a school. `currentStatus()` resolves ties deterministically by effective date, creation time and ID.

