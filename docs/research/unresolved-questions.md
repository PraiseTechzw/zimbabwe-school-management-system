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

## Learner records (PROPOSED IDs for a separate slice)

These IDs are introduced by [Findings 07 — learner records](findings/07-learner-records.md) and repeated in the [Learner Management module record](../modules/learner-management.md). They remain open and must not be answered by an engineering default.

| ID | Question | Evidence status | Needed to resolve |
| --- | --- | --- | --- |
| LM-U-01 | What do SI 13 of 2025 and other prescribed Education regulations require in an enrolment register? | SI text not verified | Product Owner / legal review |
| LM-U-02 | Do MoPSE ED 46 / EMIS prescribe learner-level fields or identifiers? | Forms and schemas not verified | Product Owner / MoPSE guidance |
| LM-U-03 | Is any mandated national learner identifier in use, and may systems store ZIMSEC candidate numbers? | No primary source found (LRN-F3) | Product Owner / legal review |
| LM-U-04 | Who is controller/processor for a deployment, and what DPO/licence duties apply? | CDPA duties apply; application unresolved (LRN-F4) | Product Owner / legal review |
| LM-U-05 | What lawful basis and guardian-verification process apply to routine school records? | Guidance found; policy unresolved | Product Owner / legal review |
| LM-U-06 | What retention/deletion schedule and legal-hold process apply to learner records? | No period found; U-15 remains open | Product Owner / legal review |
| LM-U-07 | Which roles may view or manage learner records; should `school_member` have any access? | No approved role policy | Product Owner |
| LM-U-08 | What status vocabulary and transitions are valid? | Unresolved | Product Owner |
| LM-U-09 | Should a learner record carry external EMIS/ZIMSEC identifiers, and under what provenance rules? | Unresolved | Product Owner |
| LM-U-10 | What data-portability/export format is required, and to whom may it be given? | Unresolved | Product Owner / legal review |
| LM-U-11 | When an Academic Structure offering is deleted or retired while learners reference it, should placement be cleared, blocked or migrated? | No approved placement-lifecycle policy; restrictive FK is PROPOSED | Product Owner + Academic Structure owner |

## Handling rule

Every unresolved question remains open until a source is added to the [source register](source-register.md) and the corresponding finding is updated with a source ID. An interview answer must be classified **INTERVIEW VALIDATED**, not promoted to OFFICIAL by assumption.
