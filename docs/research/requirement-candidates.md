# Functional requirement candidates — PROPOSED

**Status:** ALL CANDIDATES ARE PROPOSED. None is approved, implemented or tested. Each is linked to findings and a proposed workflow; see [../modules/platform-foundation/traceability-matrix.md](../modules/platform-foundation/traceability-matrix.md).

ID gaps are intentional: they preserve the IDs assigned during batch research.

| ID | PROPOSED requirement candidate | Evidence | Workflow | Validation needed |
| --- | --- | --- | --- | --- |
| PF-001 | Store EMIS code, registration number, school name and ward/district/province; code formats configurable | Findings 01 F1–F5; C-13 | WF-01 | MoPSE register/circular (U-06, U-07) |
| PF-002 | Model Government/non-Government, registration/provisional status, registered/satellite/unregistered status and mother-school link | Findings 01 F1, F9; OFF-01 | WF-01 | Current Education Act + MoPSE status rules |
| PF-003 | Support school-level capture and consolidation to district/provincial/national views | OFF-01–OFF-03 | WF-01 | EMIS process documentation (U-16) |
| PF-004 | Maintain annual institution master list with duplicate checks | OFF-02 | WF-01 | MoPSE master-list rules |
| PF-005 | Keep ownership/funding categories configurable, not hard-coded | OFF-01, OFF-11 | WF-01 | Official taxonomy (U-12) |
| PF-006 | Capture DPO/controller-licence and child-data status metadata, applicability undecided | LAW-02, REG-01, OBS-02 | WF-06/WF-07 | Legal review (U-01) |
| PF-010 | Configurable school type including government/non-government, satellite and mother-school link | Findings 02 F3, F10 | WF-01 | U-06/U-16 |
| PF-011 | Optional units: site/campus → department → stage → grade/form → class → stream; school mandatory | Findings 02 F14–F20 | WF-03 | U-09…U-12 |
| PF-012 | Role catalogue with effective dates: Head, Deputy, Senior Master/Lady, HOD, TIC, teacher, bursar, secretary, exams officer | Findings 02 F4, F7, F8 | WF-03/WF-04 | U-11 |
| PF-013 | Governance bodies: School Parents Assembly, SDC with configurable membership, Board/Responsible Authority, parent committee | Findings 02 F1, F2, F6 | WF-03/WF-05 | U-03/U-11 |
| PF-014 | Configurable reporting lines and delegations, not a fixed hierarchy | Findings 02 F11, F6 | WF-04 | U-11 |
| PF-015 | Stage model ECD A/B → Grades 1–7 → Forms 1–4 → Lower 6 → Upper 6 with module grouping | Findings 02 F13–F15 | WF-03 | U-09 |
| PF-016 | Qualification links: Grade 7, ZJC (legacy flag), O-Level, A-Level, ZELA | Findings 02 F13, F16 | WF-02/WF-03 | U-08 |
| PF-017 | Streams carry explicit semantics (parallel class or subject pathway) | Findings 02 F18, F19, F21 | WF-03 | U-10 |
| PF-018 | Boarding/hostel and day/day-boarding attributes without mandatory minimums | Findings 02 F3, F17 | WF-01/WF-03 | U-12 |
| PF-020 | Consent/lawful-basis and guardian-consent evidence for child data | LAW-02 ss10–12, 26; REG-02 | WF-06 | Legal review |
| PF-021 | Data-subject access, correction and deletion with child representation rules | LAW-02 ss13–14, 26 | WF-06 | U-02 |
| PF-022 | Retention controls tied to "no longer than necessary", with approved schedules | LAW-02 s13(g) | WF-08 | U-15 |
| PF-023 | Risk-appropriate technical and organisational security measures | LAW-02 s18(4) | WF-04/WF-06 | Legal + Product Owner |
| PF-024 | Breach workflow capturing awareness time for 24-hour POTRAZ deadline | LAW-02 s20; REG-01 s17 | WF-07 | U-02 |
| PF-025 | DPO/licence status metadata | REG-01; OBS-02 | WF-01/WF-07 | U-01 |
| PF-026 | Cross-border transfer assessment fields and approvals | LAW-02 ss28–29 | WF-06 | U-05 |
| PF-027 | Audit evidence sufficient to demonstrate compliance; log content undecided | LAW-02 s24; LAW-03 s23 | WF-04–WF-07 | U-04 |
| PF-030 | Academic years with term dates, source attribution, year and counting-basis metadata | PRAC-09–PRAC-12 | WF-02 | U-08/U-09 |
| PF-031 | Separate, importable examination-calendar entity; O/A-Level source pending | PRAC-12 | WF-02 | Official ZIMSEC source |
| PF-032 | Stakeholder role catalogue: learner, parent/guardian, teacher, Head, Deputy, Senior Teacher, bursar/accounting assistant, SDC officers, responsible authority, Ministry official | LAW-03, OFF-04, OFF-10, RES-02 | WF-03/WF-04 | U-11; interviews |
| PF-033 | Least-privilege RBAC with explicit permission sets and periodic review | STD-01 AC-6; STD-02 (external, RESEARCH) | WF-04 | Product Owner; not Zimbabwean law |
| PF-034 | Separation-of-duties controls for finance initiation/certification/approval and unanimous decisions | OFF-04; STD-02 (external) | WF-05 | U-11; legal review |
| PF-035 | Time-bounded, revocable delegations with acting-role records | LAW-03 ss65–66; OFF-04 | WF-04 | U-11 |
| PF-036 | Risk-tiered authentication; MFA for privileged, finance and personal-data roles; password/session rules benchmarked to SP 800-63B-4 as an **external standard** | LAW-02 s18(4); STD-03/04 (external) | WF-04/WF-06 | Product Owner; not Zimbabwean law |
| PF-037 | Audit events for personal-data access, permission changes and privileged/financial actions; retention undecided | LAW-02 s24; OFF-04; STD-01 (external) | WF-04–WF-07 | U-04 |
| PF-038 | Breach workflow capturing awareness time for POTRAZ 24h / possible 72h individual clocks | LAW-02 s20; OBS-02 (72h unverified) | WF-07 | U-02 |

## Candidates from research batch D (PROPOSED)

Full evidence and caveats: [findings/04-configuration-and-operations.md](findings/04-configuration-and-operations.md).

| ID | PROPOSED requirement candidate | Evidence | Workflow | Validation needed |
| --- | --- | --- | --- | --- |
| PF-040 | Configurable academic year, term structure, level/type, registration status, approved fees, premises, contacts and reporting templates; no hard-coded codes | OPS-02, OPS-06, OPS-07 | WF-01/WF-02/WF-08 | U-13 |
| PF-041 | Direct-entry and spreadsheet-import paths with validation against annual census templates | OPS-06, OPS-07, OPS-03 (snippet) | WF-01 | U-16; original PDFs |
| PF-042 | Distinct school data domain with role-based access and auditable cross-school access; **not** a mandated tenant topology | OPS-01 | WF-04 | U-17; ADR |
| PF-043 | Controller/processor assignment, processor contract terms, breach contacts and transfer controls before shared-service onboarding | OPS-01 ss18, 28–29 | WF-06/WF-07 | U-01, U-05 |
| PF-044 | Offline capture, queued sync, reconciliation, spreadsheet import/export and essential-operation prioritisation under intermittent power/connectivity | OPS-05, OPS-06, OPS-08, OPS-09 | WF-08 | U-14; school-level measurements |
| PF-045 | Record device/power/connectivity capability per school; do not generalise from the 113-school cohort | OPS-08 | WF-01/WF-08 | U-14 |
| PF-046 | Backup scope, retention, restore testing, incident runbooks and evidence for 24-hour breach reporting; RPO/RTO unconfirmed | OPS-01, OPS-06, OPS-10 | WF-07 | U-15 |
| PF-047 | Data-recovery steps and protected emergency contact/release records in continuity procedures | OPS-10 | WF-07 | Stakeholder review |
| PF-048 | Onboarding capture: registration/provisional status, certificate details, level, premises, head/contacts, fee settings, reporting owner, census configuration | OPS-02, OPS-03/OPS-04 (snippets), OPS-06 | WF-01 | U-16; original PDFs |
| PF-049 | Verification/approval steps mirroring school → district → HQ flow, preserving head-certification/audit trails | OPS-03 (snippet), OPS-06 | WF-01 | U-16 |

## Approval status

| Gate | Status |
| --- | --- |
| Evidence attached | Partial — several sources are indexed excerpts (R-01) |
| Stakeholder validation | Not started (R-11) |
| Product Owner approval | **Not granted** — issue `P1-G01` open |
| Implementation authorised | **No** |
