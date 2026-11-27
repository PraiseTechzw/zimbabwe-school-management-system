# Platform Foundation — product discovery (PROPOSED)

**Status:** EVERYTHING IN THIS DOCUMENT IS PROPOSED UNTIL THE PRODUCT OWNER APPROVES `P1-G01`. **Date:** 2026-11-27.

## 1. Problem definition (PROPOSED)

Zimbabwean schools operate with legally defined identities, governance roles, year-specific calendars and child-data obligations, while published evidence shows varied institutional structures and incomplete identifier mapping. Before any school functionality is built, the platform needs an evidence-backed foundation that can describe *schools, their structure, their people, their authority relationships and their data obligations* without hard-coding unverified practices.

Evidence: LAW-03, OFF-01–OFF-04, PRAC-03–PRAC-12, LAW-02, REG-01/02. Full detail in [../../research/findings/](../../research/findings/).

## 2. Stakeholders (PROPOSED)

| Stakeholder | Evidence basis | Interest |
| --- | --- | --- |
| School Head / Deputy / Teacher-in-Charge | OFF-04, OFF-10 | Authority, records, operations |
| Teachers / HODs / exams officer | OFF-04, PRAC-04 | Structure and exam workflows |
| Bursar / finance committee / accounting assistant | OFF-04, RES-02 | Records and separation of duties |
| SDC, School Parents Assembly, Board/Responsible Authority | LAW-03 s36, OFF-04, PRAC-01/02 | Governance and accountability |
| Parent / guardian / learner | LAW-02 s26, REG-02 | Child data and access rights |
| MoPSE / district / provincial officials | OFF-01–OFF-03, LAW-03 ss65–66 | EMIS, inspection, consolidation |
| POTRAZ / data protection reviewers | LAW-02, REG-01, OBS-01/02 | Compliance evidence |
| Product Owner | Governance rule | Approval gate |

## 3. Scope candidates (PROPOSED)

**In scope for discovery:** school identity master data; institutional types/status; organisational structure; academic years/terms and exam windows; stakeholder roles; authentication need; RBAC/least privilege; delegation; audit/accountability; configuration catalogue; privacy obligations; multi-school data expectations; connectivity/resilience; onboarding workflow.

**In scope for future implementation only after approval:** none yet. This milestone produces candidates, not features.

## 4. Proposed workflows

See [../../research/proposed-workflows.md](../../research/proposed-workflows.md): WF-01 school record creation, WF-02 academic year/term setup, WF-03 organisational structure, WF-04 delegation, WF-05 finance separation of duties, WF-06 child data request, WF-07 breach response, WF-08 configuration change. Every workflow is PROPOSED and several carry unresolved questions.

## 5. Functional requirement candidates (PROPOSED)

Full table with IDs and evidence links: [../../research/requirement-candidates.md](../../research/requirement-candidates.md). Summary groups:

- School identity and status (PF-001–PF-006, PF-010, PF-018).
- Organisational structure and stages (PF-011–PF-017, PF-030–PF-032).
- Privacy and data-subject processes (PF-020–PF-027).
- Permissions, delegation and audit (PF-033–PF-038).

## 6. Non-functional requirement candidates (PROPOSED)

Full table: [../../research/non-functional-requirements.md](../../research/non-functional-requirements.md). Summary: privacy by design, child-data safeguards, configurable retention, risk-appropriate security, 24-hour breach capability, demonstrable compliance, least privilege, separation of duties, risk-tiered authentication, year-specific dates as data, source/version metadata for configuration, unverified identifiers, cross-border assessment, duplicate detection. Operations-specific candidates from research batch D are appended there when available.

## 7. Business rules requiring validation

| ID | Rule candidate | Why it needs validation |
| --- | --- | --- |
| BR-01 | EMIS code and registration number are required school identifiers | Field names from unofficial form copy (C-13); syntax/canonical status unverified (U-06) |
| BR-02 | Satellite schools link to a registered mother school and lack exam-centre status until registered | OFF-01; lifecycle rules unverified |
| BR-03 | School Head is Chief Accounting Officer; Deputy acts in absence | OFF-04; per-school governance may differ (U-11) |
| BR-04 | Finance payments require distinct initiator, certifier/approver and signatory pair | OFF-04 documented practice; not a universal legal rule |
| BR-05 | SDC has at least nine members with five elected parents | Conflicting with older SI (C-02); current regulation needed |
| BR-06 | Finance subcommittee decisions are unanimous; accounting assistant does not deliberate | OFF-04; verify with interviews |
| BR-07 | Child data requests require verified guardian representation | LAW-02 s26, REG-02; legal review needed |
| BR-08 | Breach awareness starts the 24-hour clock | LAW-02 s20, REG-01 s17; scope/applicability unresolved |
| BR-09 | Term dates are year- and source-specific | PRAC-09–PRAC-11; counting conflict C-07 |
| BR-10 | Stream semantics are explicitly recorded, never inferred | C-05 |
| BR-11 | School is the only mandatory organisational unit | Supported by varied school documents; not a legal rule |

**None of these rules is approved.**

## 8. Privacy/security requirements (PROPOSED, legal review required)

- LAW-02: consent/lawful basis, minimisation, retention, security, breach notification, data-subject rights, child representation, cross-border controls.
- REG-02: privacy by design/default for children's data and written guardian consent.
- REG-01: controller licence/DPO tiers; applicability to public schools unresolved (U-01).
- LAW-02 s24: demonstrable internal compliance mechanisms.
- STD-01–STD-04 (external benchmarks only): least privilege, separation of duties, current authentication/session guidance. **Do not present these as Zimbabwean law.**

## 9. Acceptance criteria candidates (PROPOSED for the specification, not for code)

1. Each approved requirement has at least one source ID with an evidence classification.
2. No requirement is derived solely from ASSUMPTION or PROPOSED DESIGN.
3. Conflicting evidence is recorded with both positions and an explicit resolution or open question.
4. Year-specific dates and unverified identifiers are represented as metadata, not constants.
5. Privacy and security candidates have a legal-review status field.
6. Every future test trace begins from an approved requirement; no future implementation/test status is claimed now.
7. Product Owner approval is recorded before any implementation task moves to Ready.

## 10. Dependencies

- Product Owner approval gate `P1-G01`.
- Completion of research batch D (`P1-R13`, `P1-R14`, `P1-R16`, `P1-R17`, `P1-R18`) and gap closure.
- Stakeholder interview programme `P1-R19` (not started).
- Legal review of U-01–U-05 and U-15.
- Accepted ADR for any architectural decision (none in this milestone).

## 11. Risks

See [../../research/risks.md](../../research/risks.md). Top risks: primary-source access (R-01), generalising school-specific practice (R-02), hard-coded dates (R-03), governance mislabelling (R-04), child-data sensitivity (R-05), breach/licence exposure (R-06), identifier ambiguity (R-07), no interviews (R-11).

## 12. Out-of-scope features (STRICT)

Admissions; attendance; finance transactions; academics/assessment; reporting products; learner records as an implemented feature; HR; boarding; dashboards or UI; Laravel feature code; school configuration implementation; academic calendar implementation; user/permission implementation; school-specific database migrations; architectural changes without ADR; conversion of assumptions into confirmed requirements.

## 13. Approval statement

**Not approved.** This document is a candidate for the Product Owner gate `P1-G01`. Implementation is not authorised.
