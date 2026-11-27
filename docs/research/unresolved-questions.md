# Unresolved questions

**Status:** OPEN. These questions block confirmation of requirements. They are not assumptions and must not be silently answered.

## Legal and regulatory

| ID | Question | Evidence status | Needed to resolve |
| --- | --- | --- | --- |
| U-01 | Is a public school a "controller" requiring a licence under S.I. 155 of 2024? | Conflicting guidance (C-10) | Statutory/legal opinion; POTRAZ clarification |
| U-02 | Is individual notification after a breach 72 hours, and is that statutory or guidance? | Secondary source only (C-11) | Verify Act/regulations text |
| U-03 | What are the current SDC composition rules and which SI supersedes SI 87/1992? | Conflicting (C-02) | Current MoPSE regulations/SI |
| U-04 | What IT audit-log content and retention are legally expected for school data? | No Zimbabwean law found | Legal review; MoPSE records guidance |
| U-05 | What cross-border transfer approvals apply to cloud hosting of learner data? | LAW-02 ss28–29 establish controls; application unresolved | Legal/Product Owner decision |

## Identity and structure

| ID | Question | Evidence status | Needed to resolve |
| --- | --- | --- | --- |
| U-06 | What is the authoritative syntax and owner of the EMIS code and school registration number? | Unofficial form copy only (C-13) | MoPSE school register/circular |
| U-07 | Is a "UPI" a school identifier, learner identifier or neither? | No verified definition found | MoPSE/official source |
| U-08 | What is the current role of ZJC in the curriculum and qualifications? | Conflicting (C-06) | MoPSE curriculum + ZIMSEC source |
| U-09 | Is there an official Grade ↔ Form crosswalk? | Inferred only (C-04) | MoPSE document |
| U-10 | Does "stream" mean subject pathway, parallel class, or both by context? | Conflicting (C-05) | Stakeholder interviews + official documentation |
| U-11 | Which body governs a given school: SDC, Board of Governors, Responsible Authority, or multiple? | Context-dependent (C-03) | Per-school governance documents; interviews |
| U-12 | Are Departments, campuses and hostels named/structured consistently across government schools? | Only isolated school documents | Multi-school sample; interviews |

## Operations and configuration

| ID | Question | Evidence status | Needed to resolve |
| --- | --- | --- | --- |
| U-13 | Which school-level settings genuinely vary and require configuration? | Mostly assumptions | Stakeholder interviews; observed school documents |
| U-14 | What connectivity, device and power constraints must the product design for? | POTRAZ 2025 national figures (OPS-05) vs 2019 school-level constraints (OPS-06); no current school-level measurement | School-level measurements; stakeholder interviews |
| U-15 | What backup frequency, RPO/RTO and retention periods are required for learner records? | No statutory or official target found (OPS-01, OPS-06, OPS-10) | Legal + stakeholder decision |
| U-16 | What official onboarding steps and evidence are required to open a school? | Education Act (OPS-02) + indexed MoPSE snippets (OPS-03/OPS-04); exact extracts unverified | Original MoPSE PDFs and process documentation |
| U-17 | Does one deployment serve multiple schools, and under what data-access boundaries? | Evidence supports multi-school data flow, not a tenancy design | Product Owner decision + ADR |

## Process

| ID | Question | Evidence status | Needed to resolve |
| --- | --- | --- | --- |
| U-18 | Which stakeholders must validate documented-practice findings? | Interview programme planned, none conducted | Execute `P1-R19` with consent |
| U-19 | Which PROPOSED candidates will the Product Owner approve for the Phase 1 specification? | All are PROPOSED | Approval gate `P1-G01` |

## Handling rule

Every unresolved question remains open until a source is added to the [source register](source-register.md) and the corresponding finding is updated with a source ID. An interview answer must be classified **INTERVIEW VALIDATED**, not promoted to OFFICIAL by assumption.
