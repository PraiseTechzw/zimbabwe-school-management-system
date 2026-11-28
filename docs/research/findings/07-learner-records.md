# Learner record discovery — findings and decision requests

**Status:** EVIDENCE-IN-PROGRESS. All design conclusions below are **PROPOSED DESIGN** unless explicitly labelled otherwise. No interview was conducted, no Product Owner approval is recorded for a learner-record requirement, and no Zimbabwean statute or MoPSE instrument reviewed here prescribes the fields implemented in this branch.
**Scope:** a foundational, school-scoped learner record — not admissions, enrolment, guardians, attendance, assessment, health, safeguarding or finance.
**Access date:** 2026-11-28.

## Classification

| Class | Meaning |
| --- | --- |
| LEGAL | Statute, regulation or gazetted legal instrument. |
| OFFICIAL | Government/regulator/UN official publication, portal or dataset. |
| DOCUMENTED PRACTICE | Observed operational artifact or process, not a national mandate. |
| RESEARCH | Academic/standards/reference material; not binding Zimbabwean law. |
| PROPOSED DESIGN | Engineering consequence of verified findings; not a requirement. |
| ASSUMPTION | Unverified; recorded only where unresolved. |

## Sources reviewed

| ID | Source | Class | Access |
| --- | --- | --- | --- |
| LRN-S01 | [Education Act [Chapter 25:04]](https://atszim.org/wp-content/uploads/2025/10/education-act.pdf) (Veritas reference: https://www.veritaszim.net/node/3474) | LEGAL | PDF fetched and extracted. |
| LRN-S02 | [Cyber and Data Protection Act [Chapter 12:07], No. 5 of 2021](https://www.veritaszim.net/sites/veritas_d/files/Cyber%20%26%20Data%20Protection%20Act%20Cap1207%20No%205%20of%202021%20gaz%202022-03-11.pdf) (Veritas page: https://www.veritaszim.net/node/5522) | LEGAL | PDF fetched and extracted. Correct citation is Chapter 12:07, not the older Chapter 11:11/11:13 wording in some secondary material. |
| LRN-S03 | [POTRAZ Guideline on Processing of Children's Personal Information (CDPG 2 of 2024)](https://www.veritaszim.net/sites/veritas_d/files/02%20Processing%20of%20Children%27s%20Personal%20Information.pdf) (page: https://www.veritaszim.net/node/7801) | OFFICIAL regulator guideline | PDF fetched and extracted. Guidance, not an Act. |
| LRN-S04 | [Statistical Regulation SI 155 of 2024](https://www.nangozim.org/wp-content/uploads/2026/09/SI20241.pdf) (Veritas context: https://www.veritaszim.net/node/6774) | LEGAL | PDF fetched via a civil-society mirror; official host not reached. |
| LRN-S05 | [POTRAZ CDPA Implementation Guidelines 2025](https://www.veritaszim.net/sites/veritas_d/files/Cyber%20and%20Data%20Protection%20Act%20Implementation%20Guidlines.pdf) (page: https://www.veritaszim.net/node/7966) | OFFICIAL regulator guideline | PDF fetched and extracted. |
| LRN-S06 | [UNESCO/UIS ISCED 2011 mapping for Zimbabwe](https://download.uis.unesco.org/ISCED%20mapping/ISCED_2011_Mapping_Zimbabwe.xlsx) and [diagram](https://download.uis.unesco.org/ISCED%20DIAGRAMS/Zimbabwe_ENG.jpg) | RESEARCH / international statistical standard | Files fetched. Not Zimbabwean law. |
| LRN-S07 | [ZIMSEC candidate portal manual](https://dcmv1.zimsec.co.zw/Manual/Candidate/index.html) | OFFICIAL portal / DOCUMENTED PRACTICE | Page fetched. Exam-candidate context only. |
| LRN-S08 | [MoPSE legacy EMIS portal](http://www.emis.co.zw/MoPSE/) | DOCUMENTED PRACTICE (limited) | Public menu visible; data forms login-gated. |
| LRN-S09 | Existing repository corpus: `docs/research/findings/01-identity-and-types.md`, `02-organisational-structures.md`, `04-configuration-and-operations.md`, `05-privacy-and-legislation.md`, `unresolved-questions.md`, `source-register.md`, `conflicting-evidence.md` | mixed (LEGAL/OFFICIAL/RESEARCH/ASSUMPTION) | In-repo, baseline `98e8226`. |

**Not verified / inaccessible:** MoPSE `mopse.gov.zw` PDFs and annual statistical reports (DNS failure), POTRAZ direct host (DNS failure), ZimLII (HTTP 403), full text of SI 13 of 2025 Education (General) Regulations, ED 46 census form fields, and EMIS learner-level schemas. No claim is made about their contents.

## Findings

### LRN-F1 — School education is a legal entitlement; records duties exist, but no statutory learner field list was verified

- **LEGAL (LRN-S01 s4, s5):** “Every child shall be entitled to compulsory basic state funded education”; primary education for children of school-going age is an objective. Non-discrimination applies to admission and exclusion.
- **LEGAL (LRN-S01 s23):** the head of every non-Government school “shall keep and maintain such accounts and other records, and provide such information, as the Secretary may, from time to time, in writing require.”
- **LEGAL (LRN-S01 s69(2)(e)):** regulations may prescribe “the forms of enrolment registers and other school registers … and the manner in which information contained therein shall be furnished to the Secretary”.
- **No evidence obtained (LRN-S01, LRN-S09):** the Act text itself does not enumerate mandatory learner data fields. The prescribed forms were not accessible.
- **PROPOSED DESIGN:** store a minimal, extensible school-owned record; do not assert that any field is legally mandatory. Keep field additions configurable and reviewed.

### LRN-F2 — “Child of school-going age” is regulation-defined, not fixed in the Act

- **LEGAL (LRN-S01 s2):** “child of school-going age” means a child of an age within such limits as may be prescribed.
- **No evidence obtained:** the prescribed limits (SI 13 of 2025 not accessible) were not verified.
- **PROPOSED DESIGN:** capture date of birth as optional, but do not hardcode age bands, eligibility rules or automatic age-based decisions.

### LRN-F3 — No national learner identifier was found; this is not proof that none exists

- **No evidence found (LRN-S01, LRN-S02, LRN-S04, LRN-S07, LRN-S08, LRN-S09):** no accessible primary source establishes a Zimbabwe-wide mandatory learner/student number. The repository's U-07 (“Is a 'UPI' a school identifier, learner identifier or neither?”) remains open; U-20/U-24 in unmerged admissions discovery are likewise open.
- **DOCUMENTED PRACTICE (LRN-S07):** ZIMSEC candidate interactions use centre number, candidate number, session, level, year, candidate name and date of birth — these are exam-candidate identifiers, not a national learner ID.
- **PROPOSED DESIGN:** issue a school-scoped internal learner reference, generated server-side. Do not label it national, statutory or ZIMSEC-compatible. Do not create a mandatory “national ID” field.

### LRN-F4 — Zimbabwe privacy law constrains a learner record even though it does not define its schema

- **LEGAL (LRN-S02 s3):** a child is a person under 18; personal information is defined broadly.
- **LEGAL (LRN-S02 s7(1)(a)–(c)):** data must be adequate, relevant, not excessive, accurate and retained no longer than necessary.
- **LEGAL (LRN-S02 s8–s13):** specified purpose, compatibility limits, controller duties, data-subject access/correction/deletion rights; rights for children are exercised by a parent or legal guardian (s26).
- **LEGAL (LRN-S02 s11–s12):** sensitive categories including health/biometric/genetic data require written consent and strict conditions.
- **LEGAL (LRN-S02 s18–s19, s28–s29):** appropriate security, 24-hour breach notification to POTRAZ, and cross-border transfer controls.
- **LEGAL (LRN-S04 ss3–17):** SI 155/2024 operationalises licensing, DPO, breach and child-data safeguards, including an assessment of high-risk processing; its official host was not reached.
- **OFFICIAL guideline (LRN-S03, LRN-S05):** guardian verification, privacy by design/default, DPIA, purpose register and no significant solely automated decisions about children. Guidance is not an Act.
- **PROPOSED DESIGN:** minimise fields, exclude health/biometric/genetic and guardian data from this slice, keep audit snapshots free of names and dates of birth, and leave retention, consent/DPIA and data-subject workflows to separate owner/legal decisions.

### LRN-F5 — Grade/form stages are documented; no universal learner-record schema was verified

- **RESEARCH / OFFICIAL statistical mapping (LRN-S06):** ISCED mapping shows ECD, primary Grades 1–7 with Grade 7 certificate, lower secondary Forms 1–4 with O Level, and upper secondary Forms 5–6 with A Level.
- **DOCUMENTED PRACTICE (LRN-S09):** existing corpus records school-specific documents, transfer letters and varying stage labels; `conflicting-evidence.md` C-04 and U-09 record an unresolved Grade↔Form crosswalk.
- **PROPOSED DESIGN:** reuse the already-implemented Academic Structure hierarchy for optional placement. Do not seed levels, grades or a crosswalk, and do not duplicate structure data in the learner table.

### LRN-F6 — Prior admissions work is a separate, unmerged workstream

- **In-repo evidence (`feat/p1-admissions-domain-foundation`, `docs/modules/admissions.md`; unmerged local branch):** “An application is a prospective enquiry, not a learner record”; the module explicitly excludes learner/student records, enrolment and audit integration, and states that no admissions policy is approved.
- **In-repo evidence (`docs/p1-admissions-discovery`, `docs/research/findings/06-admissions.md`; unmerged local branch):** decisions requested under `P1-R20` (#53); “This does not authorise learner creation, enrolment, EMAP integration, document storage or fee collection”; engineering recommendation asks for “a separate issue for any learner-record or enrolment work”.
- **PROPOSED DESIGN:** this branch is that separate learner-record foundation. It does not convert admission applications into learners and does not touch admissions branches or files.

### LRN-F7 — Baseline architecture already supplies the required foundation

- **Verified baseline (`98e8226`):** school membership + `ResolveSchoolContext` + `SchoolContext` + `ScopesToCurrentSchool`; config-driven `EnsurePermission`; `config/authorization.php` permission catalogue; `AuditEventRecorder` with allow-listed actors/sources, key-based redaction and transactional domain call sites; Academic Structure composite FKs that prevent cross-school parent links.
- **Verified baseline:** no learner/student/pupil table, route, domain, factory or contract exists.
- **PROPOSED DESIGN:** add a learner domain that reuses these foundations rather than reimplementing tenancy, permissions or audit.

## Unresolved questions (PROPOSED IDs; not decisions)

| ID | Question | Owner |
| --- | --- | --- |
| LM-U-01 | What do SI 13 of 2025 and other prescribed Education regulations require in an enrolment register? | Product Owner / legal review |
| LM-U-02 | Do MoPSE ED 46 / EMIS prescribe learner-level fields or identifiers? | Product Owner / MoPSE guidance |
| LM-U-03 | Is there any mandated national learner identifier, and may systems store ZIMSEC candidate numbers? | Product Owner / legal review |
| LM-U-04 | Who is controller/processor for a deployment, and what DPO/licence duties apply? | Product Owner / legal review |
| LM-U-05 | What lawful basis and guardian-verification process apply to routine school records? | Product Owner / legal review |
| LM-U-06 | What retention/deletion schedule and legal-hold process apply to learner records? | Product Owner / legal review |
| LM-U-07 | Which roles may view or manage learner records; should `school_member` have any access? | Product Owner |
| LM-U-08 | What status vocabulary and transitions are valid (registration, transfer, completion, exit)? | Product Owner |
| LM-U-09 | Should a learner record carry external identifiers (EMIS/ZIMSEC), and under what provenance rules? | Product Owner |
| LM-U-10 | What data-portability/export format is required, and to whom may it be given? | Product Owner / legal review |
| LM-U-11 | When an Academic Structure offering is deleted or retired while learners reference it, should placement be cleared, blocked or migrated? | Product Owner + Academic Structure owner |

## Handling rule

A documented practice from one school, an overseas standard or an engineering default cannot be promoted to LEGAL or OFFICIAL by this document. New sources must be added to `docs/research/source-register.md` and new questions to `docs/research/unresolved-questions.md`; promotion requires a recorded Product Owner decision.
