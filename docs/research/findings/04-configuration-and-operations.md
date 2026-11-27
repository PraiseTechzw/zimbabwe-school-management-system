# Findings 04 — configuration, isolation, infrastructure, resilience and onboarding (topics 13, 14, 16, 17, 18)

**Status:** EVIDENCE-IN-PROGRESS. All requirement candidates are **PROPOSED**. Access date: `2026-11-27`. Several MoPSE statistical PDFs were not directly fetchable; indexed snippets are marked as such.

## Source IDs used here

| ID | Source | Class | Direct fetch? |
| --- | --- | --- | --- |
| OPS-01 | Cyber and Data Protection Act [Cap 12:07] — official POTRAZ PDF | LEGAL | Yes (official regulator host) |
| OPS-02 | Education Act [Cap 25:04] (Veritas mirror) | LEGAL (mirror) | Yes |
| OPS-03 | MoPSE 2025 Annual Statistical Report | OFFICIAL | No — indexed snippets only |
| OPS-04 | MoPSE 2026 Annual Statistical Report | OFFICIAL | No — indexed snippets only |
| OPS-05 | POTRAZ 2025 Second Quarter Abridged Sector Performance Report | OFFICIAL | Yes, with internal percentage conflict (C-14) |
| OPS-06 | Zimbabwe EMIS Peer Review Report (ADEA/MoPSE task force) | OFFICIAL | Yes (2019 conditions) |
| OPS-07 | MoPSE EMIS portal (live) | OFFICIAL | Yes — navigation evidence only |
| OPS-08 | UNICEF: ICT devices handed over for digital learning | OFFICIAL | Yes — project-specific, not national coverage |
| OPS-09 | World Bank Country Economic Update: Electrifying Zimbabwe's Growth (13 Dec 2023) | RESEARCH (multilateral) | Yes |
| OPS-10 | Disaster Risk Management and Resilience in Schools Manual (UNICEF/MoPSE-aligned) | OFFICIAL (guidance) | Yes — school safety, **not** an IT backup standard |

## Topic 13 — configuration requirements

**Confirmed**

1. (OPS-02) The Education Act defines a **school year** as twelve months ending 31 December, and a **term** as a period into which the teaching school year is divided. A "school" means a pre-school, primary or secondary school.
2. (OPS-02) Legally visible configuration points exist: non-Government school applications in prescribed form with documents (s15), registration certificates (s19), premises tied to the certificate (s20), fees/levies requiring prior approval (s21), and record-keeping/information duties (s23).
3. (OPS-07) The live EMIS portal separates Primary and Secondary entry/import workflows and exposes error/data reports.
4. (OPS-06) All schools completed a standard **ED 46** questionnaire; data moved through school → district → province → head-office layers. This is 2019-era evidence.

**Gaps**

5. No verified source found for term dates, class-naming syntax, learner/teacher identity formats, school-code formats or ZIMSEC centre-code formats. These remain **ASSUMPTION** until sourced.

## Topic 14 — tenant/data isolation

**Confirmed**

6. (OPS-01) CDPA covers personal information including educational and financial history and identifying numbers; a child is a person under 18; a processor acts for/on behalf of a controller under instructions.
7. (OPS-01) **s18** requires appropriate technical and organisational measures for security, integrity and confidentiality, processor guarantees and a written contract; **s19** requires POTRAZ notification **within 24 hours** of a security breach; **s24** requires internal compliance mechanisms; **ss28–29** restrict foreign transfers.

**Inferences (not confirmed)**

8. The Act does **not** state that each school must be a separate technical tenant. It does make controller/processor roles, purpose limitation, security and transfer controls explicit. Whether a school is controller, joint controller or processor is fact-specific (see C-10 and U-01).
9. Therefore the evidence supports *auditable access boundaries*, not a specific multi-tenant architecture. Any tenancy design remains **PROPOSED DESIGN** and requires an ADR.

## Topic 16 — low bandwidth and infrastructure

**Confirmed / measured**

10. (OPS-05) POTRAZ Q2 2025 reported mobile penetration **102.64%**, Internet/data penetration **81.83%**, VSAT subscriptions rising **30,907 → 40,146**, and 5G base stations **184 → 252**. Mobile Internet/data traffic grew from **114.02 PB to 130.14 PB (+14.14%)** per the table; a summary figure of 17.31% conflicts with the table (C-14) — the table is used here.
11. (OPS-06) In 2019, electricity and internet constraints forced centralised data entry despite a district-to-HQ WAN, and ED 46 was completed manually.
12. (OPS-08) UNICEF reports solarisation and offline servers hosting Learning Passport in **113 schools**, expressly for low- or no-connectivity environments. This is a project cohort, not national coverage.
13. (OPS-09) The World Bank estimated power shortages cost **6.1% of GDP annually**: 2.3% in generation/network losses and 3.8% downstream (published 13 Dec 2023).

**Inferences**

14. Connectivity figures from 2025 and infrastructure observations from 2019 describe different periods; neither alone establishes a school-level connectivity guarantee. Offline-tolerant operation is a **PROPOSED** design response, not a measured mandate.

## Topic 17 — backup, recovery and operational resilience

**Confirmed**

15. No explicit school-management-system backup frequency, RPO, RTO or restore-test standard was found in the sources consulted.
16. (OPS-01) CDPA requires security, integrity and confidentiality and a 24-hour breach-notification duty.
17. (OPS-06) The EMIS peer review found no EMIS policy and recommended a data retention/disposal policy.
18. (OPS-10) The school disaster manual emphasises emergency contact/release records, sign-out logs and family-reunification procedures; it is school-safety guidance, **not** an IT backup standard.

**Gaps**

19. Exact RPO/RTO, backup frequency and retention periods require stakeholder confirmation (U-15). No target numbers are asserted here.

## Topic 18 — school onboarding and initial configuration

**Confirmed**

20. (OPS-02) Education Act onboarding evidence: prescribed-form applications with documents (s15), registration/provisional registration certificates (s19), certificate-bound premises (s20), fee/levy approvals (s21), and record/inspection duties (ss23, 66).
21. (OPS-03, OPS-04) Indexed MoPSE snippets report figures came from the annual **ED 46** questionnaire, heads certified accuracy, and schools submitted electronic questionnaires to District Offices for verification/uploading; the 2026 report references the Annual School Census and digitalisation of school records. Exact extracts remain unverified — the PDFs could not be fetched directly.
22. (OPS-06, OPS-07) School → district → province → HQ flow and separate primary/secondary workflows are corroborated by the peer review and live portal.

## Conflicts and unresolved questions

- **C-14:** POTRAZ Q2 2025 summary says mobile data traffic rose 17.31%; its table shows 114.02 → 130.14 PB (+14.14%). The internally consistent table figure is used; the summary is not quoted as a fact.
- MoPSE 2025/2026 report wording is indexed-snippet evidence only (OPS-03/OPS-04); no exact extract is treated as verified.
- No verified source for term dates, class naming, identity formats, school-code or ZIMSEC centre-code formats.
- No published evidence for backup frequency, RPO/RTO, retention periods or a school-system resilience standard.
- Controller/joint-controller/processor status is fact-specific under CDPA (U-01).

## Risks

- Hard-coding unstated codes or calendar rules risks EMIS/ZIMSEC mismatch.
- Assuming continuous connectivity risks data loss and exclusion of rural schools.
- Weak access separation or processor governance risks CDPA breach exposure.
- Index-only MoPSE evidence may misstate onboarding requirements.
- ADEA's 2019 infrastructure findings may be stale against POTRAZ's 2025 growth figures.

## Requirement candidates — PROPOSED only

- **PROPOSED PF-040:** Configurable academic year, term structure, school level/type, registration status, approved fees, premises, head/contact details and reporting templates; no hard-coded codes (F1–F5).
- **PROPOSED PF-041:** Direct-entry and spreadsheet-import paths for primary/secondary data with validation against annual census templates (F3, F4, F22).
- **PROPOSED PF-042:** Distinct school data domain with role-based access and auditable cross-school access; **not** a mandated tenant topology (F6–F8).
- **PROPOSED PF-043:** Documented controller/processor assignment, processor contract terms, breach-escalation contacts and transfer controls before shared-service onboarding (F6, F7).
- **PROPOSED PF-044:** Offline capture, queued synchronisation, reconciliation after reconnection, spreadsheet import/export and prioritisation of essential operations under intermittent power/connectivity (F10–F14).
- **PROPOSED PF-045:** Record device/power/connectivity capability per school for operational planning; do not generalise from the 113-school cohort (F12).
- **PROPOSED PF-046:** Backup scope, retention, restore testing, incident runbooks and evidence supporting 24-hour breach reporting; exact RPO/RTO to be confirmed (F15–F19).
- **PROPOSED PF-047:** Include data-recovery steps and protected emergency contact/release records in continuity procedures (F18).
- **PROPOSED PF-048:** Onboarding capture of registration/provisional status, certificate details, level, premises, head and contacts, approved fee/levy settings, reporting owner and initial census configuration (F20–F22).
- **PROPOSED PF-049:** Verification/approval steps mirroring school → district → HQ flow, preserving head-certification/audit trails (F21, F22).

None of these are approved requirements.
