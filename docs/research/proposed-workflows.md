# Proposed workflows — PROPOSED, NOT APPROVED

**Status:** Every workflow below is a **PROPOSED DESIGN** derived from sourced findings. No workflow is an approved business process and no workflow is implemented. Access date for sources: `2026-11-27`.

## WF-01 — School record creation (PROPOSED)

**Evidence:** LAW-03 ss15/19 (registration, provisional certificates), OFF-01 (registered/satellite/unregistered, mother school), OFF-02 (master list, duplicate risks), PRAC-08 (EMIS code/registration number field names, low confidence).

1. Collect school name, official identifiers, location (ward/district/province), type and registration status.
2. Record responsible authority and governing-body type.
3. If satellite: link mother school and record authorisation evidence; flag "no exam-centre status until registered".
4. Check the institution master list for duplicates; record evidence for each identifier.
5. Save as **PROPOSED** institution record pending Product Owner approval of the identity model.

**Unresolved:** U-06, U-07, U-16. Do not treat identifier formats as validated.

## WF-02 — Academic year and term setup (PROPOSED)

**Evidence:** PRAC-09–PRAC-12 (year-specific dates, signed calendar, fake-calendar incident), OFF-09/OFF-05 (stage nomenclature).

1. Select the year (e.g. 2026) and record the authoritative source URL and publication date.
2. Enter term dates with a counting-basis field (school days vs calendar days) because sources disagree (C-07).
3. Separately record ZIMSEC examination windows with their own source and year.
4. Require review before publication; changes create an auditable revision, not a silent edit.

**Unresolved:** U-08, U-09. Never hard-code dates.

## WF-03 — Organisational structure setup (PROPOSED)

**Evidence:** OFF-05/OFF-06/OFF-09 (stages), OFF-04/OFF-10 (leadership roles), PRAC-03–PRAC-07 (varied structures), C-04/C-05 (ambiguous crosswalk and streams).

1. Choose stage model (ECD A/B → Grades 1–7 → Forms 1–4 → Lower 6 → Upper 6) with explicit mapping metadata.
2. Add optional sites/campuses, departments, classes and streams; school remains the only mandatory unit.
3. Record stream semantics explicitly ("subject pathway" or "parallel class") — do not infer from the word alone.
4. Add role assignments with effective dates; keep reporting lines configurable (R-04).

**Unresolved:** U-09…U-12.

## WF-04 — Acting authority and delegation (PROPOSED)

**Evidence:** LAW-03 ss65–66 (conditional, withdrawable delegation), OFF-04 (Deputy assumes Head's duties; Head is Chief Accounting Officer), PRAC-02 (Board delegates operations to Head).

1. Delegator records delegated permission set, start/end dates and reason.
2. Delegation requires approval by the configured authority (e.g. Responsible Authority for a governance delegation).
3. Acting role is time-bounded and revocable; expiry restores the original permission set.
4. Every grant, use, revocation and expiry creates an audit event.

**Unresolved:** U-11. No system role for "board" is confirmed.

## WF-05 — Finance separation of duties (PROPOSED)

**Evidence:** OFF-04 (Head initiates; SDC Chair/Vice-Chair certifies and approves; joint signing with countersignature; unanimous subcommittee decisions; accounting assistant excluded from deliberations), STD-02 (SoD principle).

1. Initiator (Head/Deputy) prepares the payment record.
2. Certifier/approver (SDC Chair/Vice-Chair) acts as a distinct person.
3. Signatory pair recorded with countersignature; same person may not fill two roles.
4. Record unanimity of the finance subcommittee decision and exclusions where applicable.

**Caution:** This is documented practice from a handbook and one association context, **not** a verified universal legal rule. Validate with interviews (U-11) and legal review before approval.

## WF-06 — Personal-data request by a child (PROPOSED)

**Evidence:** LAW-02 ss13–14, 26 (data-subject rights; guardian representation), REG-02 (written guardian consent, privacy by design).

1. Receive request; identify requester and whether the data subject is a child.
2. Verify guardian authority with recorded evidence before disclosing or changing data.
3. Apply access/correction/deletion within configured statutory timeframes.
4. Record the decision, evidence and outcome as an auditable event.

**Unresolved:** U-02 (notification deadlines), U-01 (controlling status).

## WF-07 — Breach response (PROPOSED)

**Evidence:** LAW-02 s20 (24 hours to POTRAZ), REG-01 s17 (24 hours), OBS-02/RES-L01 (72-hour individual notification reported, unverified).

1. Record discovery time (not report time) and affected data categories.
2. Assess risk to data subjects and notify POTRAZ within 24 hours where required.
3. If high risk, evaluate individual notification; 72 hours held as RESEARCH pending verification (C-11).
4. Preserve evidence of decision and notification.

## WF-08 — Ongoing configuration change (PROPOSED)

**Evidence:** C-07/C-08, R-03, U-13.

1. Propose change with reason, source and effective date.
2. Review by configured authority.
3. Publish new version; prior version remains readable for historical records.
4. No silent edits to historical years.

## Traceability note

These workflows link to findings in [findings/](findings/) and requirement candidates in [requirement-candidates.md](requirement-candidates.md). Links to future implementation and tests are intentionally blank in [../modules/platform-foundation/traceability-matrix.md](../modules/platform-foundation/traceability-matrix.md).
