# Non-functional requirement candidates — PROPOSED

**Status:** ALL CANDIDATES ARE PROPOSED. No target numbers are asserted without evidence. No implementation exists.

| ID | PROPOSED NFR candidate | Evidence | Validation needed |
| --- | --- | --- | --- |
| NFR-001 | Personal data processing must be proportionate and documented (privacy by design/default) | LAW-02 ss13, 18(4); REG-02 | Legal review |
| NFR-002 | Child data must support guardian representation and written-consent evidence | LAW-02 s26; REG-02 | Legal review + interviews |
| NFR-003 | Retention must be configurable and justified by purpose; no indefinite retention by default | LAW-02 s13(g) | Product Owner + legal |
| NFR-004 | Security controls must be appropriate to identified risks, with documented measures | LAW-02 s18(4) | Risk assessment |
| NFR-005 | Breach response must be capable of meeting a 24-hour regulator notification duty | LAW-02 s20; REG-01 s17 | Legal confirmation of scope |
| NFR-006 | Compliance mechanisms must be demonstrable (records and internal mechanisms) | LAW-02 s24 | Legal review |
| NFR-007 | Audit events must cover personal-data access, permission changes and privileged/financial actions; content/retention undecided | LAW-02 s24; OFF-04; STD-01 external | U-04 |
| NFR-008 | Least privilege must be the default for role and permission assignment | STD-01 AC-6; STD-02 (external standards, not Zimbabwean law) | Product Owner |
| NFR-009 | Separation of duties must be representable for finance initiation/certification/approval | OFF-04; STD-02 (external) | U-11 + legal |
| NFR-010 | Authentication strength must be risk-tiered; MFA for privileged roles; no statutory Zimbabwean MFA rule was found | LAW-02 s18(4); STD-03/04 (external) | Product Owner |
| NFR-011 | Session handling must follow a current external standard where adopted; SP 800-63B-4 supersedes rev 3 | STD-03/04 (external) | Product Owner |
| NFR-012 | Year-specific academic and exam dates must be data, never code constants | PRAC-09–PRAC-12; C-07/C-08 | Architecture review |
| NFR-013 | Every configuration change must retain source, effective date and prior version | C-07/C-08; R-03 | Architecture review |
| NFR-014 | School identifiers must be treated as unverified until an official syntax/source is obtained | OFF-02; C-13; U-06/U-07 | MoPSE source |
| NFR-015 | Cross-border hosting must support transfer assessment and approval recording | LAW-02 ss28–29 | U-05 |
| NFR-016 | Institution master data must support duplicate detection and a satellite/mother relationship | OFF-01, OFF-02 | U-16 |

## Candidates from research batch D (PROPOSED)

Evidence: [findings/04-configuration-and-operations.md](findings/04-configuration-and-operations.md). No numeric targets are asserted without a source.

| ID | PROPOSED NFR candidate | Evidence | Validation needed |
| --- | --- | --- | --- |
| NFR-017 | Offline-tolerant capture with queued synchronisation and explicit reconciliation after reconnection | OPS-05, OPS-06, OPS-08 | U-14; school-level measurements |
| NFR-018 | Spreadsheet import/export compatible with annual census workflows | OPS-06, OPS-07, OPS-03 (snippet) | U-16 |
| NFR-019 | Essential operations remain usable under intermittent power/connectivity; capability recorded per school | OPS-08, OPS-09 | Product Owner |
| NFR-020 | Access boundaries between schools must be auditable; no specific tenant topology is mandated by evidence | OPS-01 | U-17; ADR |
| NFR-021 | Processor guarantees and written contracts must be recorded before shared-service use | OPS-01 s18 | Legal review |
| NFR-022 | Backup scope, restore testing and incident runbooks must exist; exact RPO/RTO remain unconfirmed | OPS-01, OPS-06, OPS-10 | U-15 |
| NFR-023 | Emergency contact/release records must be protected and recoverable in continuity procedures | OPS-10 | Stakeholder review |

**Note:** POTRAZ Q2 2025 reported 81.83% Internet/data penetration and mobile penetration of 102.64%, while a 2019 peer review found manual ED 46 completion and centralised entry. These are different periods and measures; neither justifies a universal connectivity assumption (C-14, U-14).
