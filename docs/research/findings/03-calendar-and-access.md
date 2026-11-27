# Findings 03 — calendars, stakeholders, authentication, RBAC, delegation and audit (topics 7–12)

**Status:** EVIDENCE-IN-PROGRESS. Requirement candidates are **PROPOSED**. Access date: `2026-11-27`.

## Source IDs used here

| ID | Source | Class |
| --- | --- | --- |
| IAM-01 | Data Protection Act, Act 5/2021 copy (Veritas) — older title/chapter | LEGAL |
| IAM-02 | Education Act [Cap 25:04] (Veritas consolidated) | LEGAL |
| IAM-03 | School Development Committees Handbook (MoPSE/UNICEF) | OFFICIAL |
| IAM-04 | NIST SP 800-53 Rev 5.1 (external standard) | RESEARCH (external) |
| IAM-05 | NISTIR 7316 — NIST Model for RBAC | RESEARCH (external) |
| IAM-06 | NIST SP 800-63B-4, July 2025 | RESEARCH (external) |
| IAM-07 | NIST SP 800-63B-4 §5 session management | RESEARCH (external) |
| IAM-08 | Quick-Start Guide to Zimbabwe Data Protection Regulations (DLA Piper Africa, 2024) | RESEARCH |
| IAM-09 | MISA Zimbabwe, CDPA requirements (2025) | RESEARCH |
| IAM-10 | POTRAZ licensing implementation guideline via Veritas | OFFICIAL |
| IAM-11 | UNESCO PEER school leadership profile (archived) | RESEARCH |
| IAM-12 | "Chaos Over Fake School Calendar" (Observer, Nov 2025) | DOCUMENTED PRACTICE |
| IAM-13 | Official Zimbabwe School Calendar 2026 (School Reviews Zimbabwe) | DOCUMENTED PRACTICE |
| IAM-14 | 2025 school calendar report (Bulawayo24, Aug 2024) | DOCUMENTED PRACTICE |
| IAM-15 | ZIMSEC 2026 Grade 7 timetable (OpenClass reproduction) | DOCUMENTED PRACTICE |
| IAM-16 | Effective guidelines for financial management in Zimbabwean secondary schools (academic) | RESEARCH |

## Topic 7 — academic calendars, years and terms

**Confirmed / documented**

1. (IAM-12, IAM-13) The 2026 school year has three terms: **T1 13 Jan–1 Apr**, **T2 12 May–6 Aug**, **T3 8 Sep–3 Dec**. A Minister is quoted: "I signed the 2026 calendar two months back … Schools are opening on January 13." IAM-12 reports T1 = 57 school days and a 34-day holiday; IAM-13 gives T2 as 87 days (likely calendar vs school-day counting).
2. (IAM-14) 2025 dates differed: **T1 14 Jan–10 Apr**, **T2 13 May–7 Aug**, **T3 9 Sep–1 Dec**. Year-specific dates must not be hard-coded.
3. (IAM-15) A secondary reproduction of the ZIMSEC Grade 7 2026 timetable gives exams **21 Sep–6 Oct 2026** with sessions at 09:00 and 14:00. Official ZIMSEC timetable pages returned 404/timeout; O/A-Level timetables were not fetched.

**Inferences**

4. Calendar and examination schedules are separate data domains with independent sources and years. The "fake calendar" incident (IAM-12) shows a real risk of unofficial dates circulating.

## Topic 8 — user types and stakeholder roles

**Confirmed**

5. (IAM-02 s36, s2) Parents/guardians of children at a school form the **School Parents Assembly**; the responsible authority must cause it to establish an SDC. "Parent" includes guardian; "teacher" includes head of school.
6. (IAM-03) SDC has at least nine members: five elected parents, School Head and Deputy *ex officio*, a Senior Teacher, and a Responsible Authority representative.
7. (IAM-03, IAM-11) Named roles include School Head, Deputy, Senior Teacher, HOD, Teacher-in-Charge, teachers, non-teaching staff, SDC officers (chair, vice-chair, secretary, treasurer) and "Accounting Assistant".
8. (IAM-02 ss65–66) Ministry officials are stakeholders: the Secretary may delegate functions to Ministry employees with written Ministerial approval; authorised officials may enter schools and inspect accounts and records.
9. (IAM-16) Bursars are a distinct sampled role in school financial management; one head described a finance committee of head, deputy and bursar. The study sampled five heads, five SDC chairs/treasurers and five bursars; not nationally representative.

**Gaps**

10. No fetched source defines a "board/governing body" as a *system* role; the legal analogue is **responsible authority**. No learner representation role was evidenced in SDC composition.

## Topics 9–10 — authentication and role-based access control

**Confirmed legal position**

11. (IAM-01 s18(4)) Controllers/processors must take "appropriate technical and organisational measures" to protect data against negligent or unauthorised destruction, loss, alteration, access and other unauthorised processing; s18(6) permits the Authority to issue security standards. **No Zimbabwean statutory MFA/password rule was found.** (See [05-privacy-and-legislation.md](05-privacy-and-legislation.md) for the corrected Act citation.)

**External benchmarks — RESEARCH, not Zimbabwean law**

12. (IAM-04) NIST SP 800-53 AC-6: employ least privilege; AC-5 requires separation of duties. (IAM-05) NISTIR 7316: "no user should be given enough privileges to misuse the system"; the person authorising a paycheck should not also prepare it.
13. (IAM-06, IAM-07) SP 800-63B-4 (July 2025) supersedes revision 3: AAL2 requires two factors and at least one phishing-resistant option; single-factor passwords minimum 15 characters (8 with MFA), no periodic changes, blocklist checks, rate limiting and salted hashing; session secrets ≥64 bits, Secure/HttpOnly cookies, CSRF identifier on POST/PUT, timeouts (AAL2 24h / 1h inactivity; AAL3 12h / 15min).

## Topic 11 — school-level permissions and delegation

**Confirmed**

14. (IAM-03) School Head is **Chief Accounting Officer** and chairs the Finance Subcommittee; the Deputy assumes duties in absence. Payments: Head/Deputy initiates; SDC Chair/Vice-Chair certifies and approves; Head/Deputy signs with SDC Chair/Vice-Chair countersigning. Finance Subcommittee decisions must be unanimous; the accounting assistant "may not take part in the deliberations".
15. (IAM-02 ss65–66) Delegation to Ministry officials is conditional and withdrawable; non-Government school heads must keep accounts and records required by the Secretary; officials may inspect them.

## Topic 12 — audit trails and accountability

**Confirmed**

16. (IAM-01 ss13(g), 19–20, 24) Retain identifiable data no longer than necessary; breach notification to POTRAZ within 24 hours (IAM-08 adds affected individuals within 72 hours where high risk); s24 requires "internal mechanisms … for demonstrating such compliance".
17. (IAM-03) SDC Handbook requires minute books, numerically sequenced cash books, locked financial records, asset registers and audits.

**Gap**

18. No fetched Zimbabwean law mandates IT audit-log *content* or retention duration. IAM-04 (AC-6(9), AU family) is an external benchmark only.

## Conflicts and unresolved questions

- IAM-01 uses the superseded "Data Protection Act [Cap 11:22]" title; use [05-privacy-and-legislation.md](05-privacy-and-legislation.md) for the corrected citation.
- 2026 term 2 is reported as 67 school days (IAM-12) vs 87 days (IAM-13) — counting basis unresolved.
- Official ZIMSEC O/A-Level timetables were not retrieved.
- Licensing applicability of S.I. 155 to public schools is unresolved (IAM-08, IAM-09, IAM-10).
- No evidence found for guardian or learner access to school systems; any such role is **PROPOSED** only.

## Requirement candidates — PROPOSED only

- **PROPOSED PF-030:** Academic years with term open/close dates, source attribution and year metadata (F1–F3).
- **PROPOSED PF-031:** Separate, importable examination-calendar entity; O/A-Level source pending (F3).
- **PROPOSED PF-032:** Role catalogue: learner, parent/guardian, teacher, Head, Deputy, Senior Teacher, bursar/accounting assistant, SDC officers, responsible authority, Ministry official (F5–F10).
- **PROPOSED PF-033:** Least-privilege RBAC with explicit permission sets and periodic privilege review (F12, IAM-04/05 external).
- **PROPOSED PF-034:** Separation-of-duties controls for finance initiation/certification/approval and unanimous subcommittee decisions (F14, IAM-05 external).
- **PROPOSED PF-035:** Time-bounded, revocable delegations with acting-role records (F8, F14–F15).
- **PROPOSED PF-036:** Risk-tiered authentication: MFA for privileged/finance/personal-data roles; passwords/sessions benchmarked to SP 800-63B-4 as an **external standard**, not Zimbabwean law (F11–F13).
- **PROPOSED PF-037:** Audit events for personal-data access, permission changes and privileged/financial actions; retention to be decided (F16–F18).
- **PROPOSED PF-038:** Breach workflow capturing awareness time to support 24-hour POTRAZ / 72-hour data-subject clocks (F16).

None of these are approved requirements.
