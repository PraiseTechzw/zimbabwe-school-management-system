# Requirements traceability matrix — Phase 1 (PLANNED LINKS ONLY)

> **Post-discovery update (2026-11-27):** this matrix is retained as the discovery-time record. Implementation was subsequently released at `P1-G01` (D-08) and four slices merged via PRs #41, #43, #44 and #45. Implemented behaviour and decisions are recorded in [decision-log.md](decision-log.md), not here. The blank columns below are not a current status claim.

**Status:** Discovery traceability. At the time of discovery the last two columns were intentionally blank: no implementation or test existed in that milestone. Access date for sources: `2026-11-27`.

All requirement IDs below are **PROPOSED** and await Product Owner approval (`P1-G01`). Evidence classes are defined in [../../research/evidence-classification.md](../../research/evidence-classification.md).

| Source ID(s) | Finding | Workflow | PROPOSED requirement | PROPOSED design concept | Future implementation | Future test |
| --- | --- | --- | --- | --- | --- | --- |
| LAW-03 ss9/15/19/23; OFF-01–03; PRAC-08 | F01–F05 (identity fields, master list) | WF-01 | PF-001 | Institution / InstitutionIdentifier | *Blank — not started* | *Blank — not started* |
| LAW-03 s9; OFF-01; OPS-02 | F01, F09, F20 (status, registration) | WF-01 | PF-002 | InstitutionStatus / SatelliteRelationship | *Blank* | *Blank* |
| OFF-01–03; OPS-06/07 | F13, F22 (school→district→HQ) | WF-01 | PF-003 | Institution master data | *Blank* | *Blank* |
| OFF-02 | F04 (duplicate/code risk) | WF-01 | PF-004 | InstitutionIdentifier validation | *Blank* | *Blank* |
| OFF-01; OFF-11 | F10–F12 (ownership/funding) | WF-01 | PF-005 | Configurable categories | *Blank* | *Blank* |
| LAW-02; REG-01; OBS-02 | F1–F9 privacy findings | WF-06/WF-07 | PF-006 | ComplianceEvidence | *Blank* | *Blank* |
| OFF-01; OFF-10 | F10, F14 (satellite relationship) | WF-01 | PF-010 | SatelliteRelationship | *Blank* | *Blank* |
| OFF-05/06/09; PRAC-03–07 | F14–F20 (units) | WF-03 | PF-011 | Class / Stream / Department / Site / Hostel | *Blank* | *Blank* |
| OFF-04; OFF-10; PRAC-03/04 | F4, F7, F8 (roles) | WF-03/WF-04 | PF-012 | RoleAssignment | *Blank* | *Blank* |
| LAW-03 s36; OFF-04; PRAC-01/02 | F1, F2, F6 (governance) | WF-03/WF-05 | PF-013 | GoverningBody / ResponsibleAuthority | *Blank* | *Blank* |
| LAW-03 ss65–66; OFF-04 | F8, F14–F15 (delegation) | WF-04 | PF-014 | Delegation | *Blank* | *Blank* |
| OFF-05/06/09 | F13–F15 (stages) | WF-03 | PF-015 | Stage / GradeForm | *Blank* | *Blank* |
| OFF-07; OFF-09 | F13, F16 (qualifications) | WF-02/WF-03 | PF-016 | ExaminationWindow level | *Blank* | *Blank* |
| PRAC-05/07; C-05 | F18, F19, F21 (stream semantics) | WF-03 | PF-017 | Stream semantics flag | *Blank* | *Blank* |
| LAW-03; OFF-05 | F3, F17 (boarding) | WF-01/WF-03 | PF-018 | Hostel / day-boarding attributes | *Blank* | *Blank* |
| LAW-02 ss10–12, 26; REG-02 | Privacy findings 1–9 | WF-06 | PF-020 | Consent / PersonalDataRequest | *Blank* | *Blank* |
| LAW-02 ss13–14, 26 | Rights findings | WF-06 | PF-021 | PersonalDataRequest | *Blank* | *Blank* |
| LAW-02 s13(g) | Retention finding | WF-08 | PF-022 | ComplianceEvidence retention | *Blank* | *Blank* |
| LAW-02 s18(4) | Security finding | WF-04/WF-06 | PF-023 | Security measures metadata | *Blank* | *Blank* |
| LAW-02 s20; REG-01 s17 | Breach finding | WF-07 | PF-024 | BreachCase | *Blank* | *Blank* |
| REG-01; OBS-02 | Licensing finding | WF-01/WF-07 | PF-025 | ComplianceEvidence DPO/licence | *Blank* | *Blank* |
| LAW-02 ss28–29 | Transfer finding | WF-06 | PF-026 | ComplianceEvidence transfer | *Blank* | *Blank* |
| LAW-02 s24; LAW-03 s23 | Accountability findings | WF-04–WF-07 | PF-027 | AuditEvent | *Blank* | *Blank* |
| PRAC-09–12; C-07/C-08 | F1–F3 (calendar years) | WF-02 | PF-030 | AcademicYear / Term | *Blank* | *Blank* |
| PRAC-12 | F3 (exam timetable) | WF-02 | PF-031 | ExaminationWindow | *Blank* | *Blank* |
| LAW-03; OFF-04; OFF-10; RES-02 | F5–F10 (stakeholders) | WF-03/WF-04 | PF-032 | RoleAssignment taxonomy | *Blank* | *Blank* |
| STD-01 AC-6; STD-02 (external) | F12 (least privilege) | WF-04 | PF-033 | Permission | *Blank* | *Blank* |
| OFF-04; STD-02 (external) | F14 (SoD) | WF-05 | PF-034 | Permission separation | *Blank* | *Blank* |
| LAW-03 ss65–66; OFF-04 | F8, F14–F15 | WF-04 | PF-035 | Delegation | *Blank* | *Blank* |
| LAW-02 s18(4); STD-03/04 (external) | F11–F13 (auth) | WF-04/WF-06 | PF-036 | Risk-tiered authentication | *Blank* | *Blank* |
| LAW-02 s24; OFF-04; STD-01 | F16–F18 (audit) | WF-04–WF-07 | PF-037 | AuditEvent | *Blank* | *Blank* |
| LAW-02 s20; OBS-02 | F16 (breach clocks) | WF-07 | PF-038 | BreachCase | *Blank* | *Blank* |
| OPS-02; OPS-06/07 | F1–F5 (config points) | WF-01/WF-02/WF-08 | PF-040 | Configurable settings | *Blank* | *Blank* |
| OPS-06/07; OPS-03 snippet | F3, F4, F22 (import) | WF-01 | PF-041 | Census import/export | *Blank* | *Blank* |
| OPS-01 | F6–F8 (isolation) | WF-04 | PF-042 | Auditable school data domain | *Blank* | *Blank* |
| OPS-01 ss18, 28–29 | F6, F7 (processor) | WF-06/WF-07 | PF-043 | Processor/transfer controls | *Blank* | *Blank* |
| OPS-05/06/08/09 | F10–F14 (connectivity) | WF-08 | PF-044 | Queued synchronisation | *Blank* | *Blank* |
| OPS-08 | F12 (113-school cohort) | WF-01/WF-08 | PF-045 | School capability metadata | *Blank* | *Blank* |
| OPS-01/06/10 | F15–F19 (resilience) | WF-07 | PF-046 | Backup/runbook concept | *Blank* | *Blank* |
| OPS-10 | F18 (safety records) | WF-07 | PF-047 | Protected emergency records | *Blank* | *Blank* |
| OPS-02; OPS-03/04 snippets; OPS-06 | F20–F22 (onboarding) | WF-01 | PF-048 | Institution lifecycle | *Blank* | *Blank* |
| OPS-03 snippet; OPS-06 | F21–F22 (verification) | WF-01 | PF-049 | Approval flow | *Blank* | *Blank* |

## Traceability rules

1. A row may be promoted from PROPOSED to approved only by Product Owner decision at `P1-G01`.
2. Indexed-only sources (see [source register](../../research/source-register.md)) require original-document verification before approval.
3. External standards (STD-01–STD-04) are benchmarks, **not** Zimbabwean law.
4. The **Future implementation** and **Future test** columns remain blank until an approved requirement exists. This is a deliberate record of the approval gate, not an omission.
5. No row claims a commit, PR, migration, test, deployment or feature.
