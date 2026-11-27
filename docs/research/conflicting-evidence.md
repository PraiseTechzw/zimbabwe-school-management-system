# Conflicting evidence

**Status:** EVIDENCE-IN-PROGRESS. Where sources disagree, both positions are recorded. No silent selection has been made.

| ID | Topic | Position A | Position B | Assessment / action |
| --- | --- | --- | --- | --- |
| C-01 | Privacy law citation | LAW-01/IAM-01: "Data Protection Act [Cap 11:22]", Act 5/2021 | LAW-02: corrected republication "Cyber and Data Protection Act [Cap 12:07]", GN 492/2022 (11 Mar 2022) | Use corrected citation (LAW-02 verified directly). Legacy references remain in older documents; do not copy them into requirements. |
| C-02 | SDC composition | OFF-04/OFF-10: ≥9 members, five elected parents, Head + Deputy ex officio, senior teacher, authority representative | RES-01: earlier SI 87/1992 quoted as five members | Check current regulations before embedding any composition rule. Treat both as historical/official positions; maintain configurable membership (PROPOSED). |
| C-03 | School-body terminology | LAW-03 s36: **School Parents Assembly**; SDC established by it | PRAC-01/PRAC-02: parent committee and Board of Governors in independent schools; community usage "PTA" | Different institutional contexts. Do not assume PTA == SPA. Stakeholder validation required. |
| C-04 | Grade ↔ Form mapping | OFF-09: lower secondary grades 8–11, upper grades 12–13 | OFF-05: Forms 1–4, Lower 6, Upper 6 | A crosswalk is plausible but no explicit official mapping was retrieved. Mark ASSUMPTION until verified. |
| C-05 | "Stream" semantics | PRAC-05: A-Level Science/Commercial/Arts streams | PRAC-07: "each stream (Form 1–4) having 12 classes" | Same word used for subject pathway and parallel class. Do not define one national meaning. |
| C-06 | ZJC status | OFF-07 lists ZJC as a ZIMSEC level | OFF-09 describes four-year lower secondary to O-Level | Current curricular role of ZJC unresolved; flag as legacy pending verification. |
| C-07 | 2026 Term 2 days | PRAC-09: 67 school days | PRAC-10: 87 days | Likely school-day vs calendar-day counting. Store source and counting basis with calendar data. |
| C-08 | Term dates by year | PRAC-10 2026: 13 Jan–1 Apr; 12 May–6 Aug; 8 Sep–3 Dec | PRAC-11 2025: 14 Jan–10 Apr; 13 May–7 Aug; 9 Sep–1 Dec | Not truly contradictory — year-specific. Never hard-code dates. |
| C-09 | Exam timetable sources | PRAC-12 reproduces Grade 7 2026 dates | Official ZIMSEC pages unreachable at access time | Treat reproduced timetable as DOCUMENTED PRACTICE, not OFFICIAL; retry official source later. |
| C-10 | Controller licensing for public schools | REG-01/RES-L01 list licensing and 50+ data-subject thresholds | OBS-02 (Veritas) disputes POTRAZ guidance that Ministries/public entities must licence | Legal applicability unresolved; Product Owner/legal review required. |
| C-11 | Breach notification to individuals | OBS-02/RES-L01: 72 hours where high risk | LAW-02 text verified for 24-hour POTRAZ notification only | Do not present 72 hours as statutory until verified in the Act/regulations. |
| C-12 | School counts | OFF-03: 12,324 schools equipped with EMIS | Unverified secondary claim of 12,323 total schools | Different measures and weak second source. Do not use either as a baseline fact. |
| C-13 | School identifier definition | PRAC-08: EMIS code, registration number (unofficial form copy) | OFF-02: no individual learner ID, code compatibility score zero (2019) | Field names indicative only; syntax/canonical status unverified. |
| C-14 | POTRAZ Q2 2025 mobile data growth | OPS-05 summary: "17.31%" | OPS-05 table: 114.02 → 130.14 PB (+14.14%) | Use the internally consistent table figure; do not quote the summary percentage as fact. |
| C-15 | Infrastructure conditions over time | OPS-06 (2019): manual ED 46, centralised entry due to power/internet constraints | OPS-05 (2025): 81.83% Internet/data penetration, VSAT and 5G growth | Different periods and measures. Neither establishes current school-level connectivity. |
| C-16 | School disaster guidance vs IT backup | OPS-10: emergency contact/release records, sign-out logs, reunification | No CDPA/ADEA standard for backup frequency, RPO/RTO or restore tests | Treat OPS-10 as school-safety guidance only; do not derive IT backup targets from it. |

## Rule

A conflict may be resolved only by an additional source with higher priority in the [research plan](phase-1-research-plan.md) or by a recorded Product Owner decision. Until then, downstream documents carry both positions.
