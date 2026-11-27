# Findings 01 — school identity, types and multi-school evidence (topics 1–3)

**Status:** EVIDENCE-IN-PROGRESS. All requirement candidates below are **PROPOSED**. Access date for all sources: `2026-11-27`.

## Source IDs used here

See [../source-register.md](../source-register.md) for URLs and reliability notes.

| ID | Source | Class |
| --- | --- | --- |
| LAW-01 | Constitution of Zimbabwe, consolidated 2023 (Veritas) | LEGAL |
| LAW-02 | Cyber and Data Protection Act [Cap 12:07], No. 5 of 2021 (corrected republication, Veritas) | LEGAL |
| LAW-03 | Education Act [Cap 25:04], ss 9, 15, 19, 23, 36 | LEGAL |
| REG-01 | S.I. 155 of 2024 — licensing and Data Protection Officers (mirror) | LEGAL (mirror) |
| REG-02 | POTRAZ guideline: Processing Children's Personal Information (Veritas host) | OFFICIAL |
| OFF-01 | Zimbabwe Partnership Compact 2022–2026 (MoPSE/GPE) | OFFICIAL |
| OFF-02 | Zimbabwe EMIS Peer Review Report (ADEA/MoPSE task force) | OFFICIAL |
| OFF-03 | GPE: "Data systems transform Zimbabwe's schools" (2026) | OFFICIAL |
| DOC-01 | ED 46 (C1) school census form copy (unofficial upload) | ASSUMPTION / unverified |
| RES-01 | UNESCO PEER: non-state actors in education (Zimbabwe) | RESEARCH |
| OBS-01 | Veritas Bill Watch 27/2026 — POTRAZ inspections | RESEARCH |
| OBS-02 | Veritas Bill Watch 29/2026 — CDPA implications | RESEARCH |

## Topic 1 — school identity and configuration

**Confirmed**

1. (LAW-03) The Education Act's only statutory school classification is **Government** or **non-Government** (s9); the Minister may determine further categories. Non-Government schools must be registered (s15), with registration or provisional certificates (s19), and must keep records and supply information (s23).
2. (LAW-01) Constitution s75(2)–(3) protects independent educational institutions and allows registration by law; s57 protects privacy.
3. (OFF-01, OFF-02) MoPSE maintains an annually updated master list/directory of schools and uses the **ED 46** census instrument. EMIS data are consolidated centrally and analysed at MoPSE head office.
4. (OFF-02) The 2019 EMIS peer review reported no individual learner identifier and a zero compatibility score for school codes/location coordinates. This is a 2019 finding, not a current state.
5. (OFF-03) The revised EMIS is administered at school level with validation formulas and used at district, provincial and national levels; 12,324 schools were reported equipped (year not independently verified against a gazette).

**Low-confidence / unverified**

6. (DOC-01) A search-indexed copy of an ED 46 form refers to an **EMIS Code** and **School Registration Number**, and asks satellite schools for the mother school's EMIS code and registration number. The form copy was not retrieved from an official host. Treat field names as indicative only.
7. No verified official source was found defining the *syntax* of an EMIS code or registration number. No verified definition of a "UPI" school identifier was found in this batch.

**Inferences (not confirmed)**

8. The evidence supports school identity as institutional master data with multiple identifiers; it does **not** prove that one identifier is canonical across systems.

## Topic 2 — school types and institutional structures

**Confirmed**

9. (LAW-03, OFF-01) Beyond the Government/non-Government statutory split, the Partnership Compact distinguishes **registered**, **satellite** (authorised before registration, supervised by a registered "mother" school, no exam-centre status until registered) and **unregistered** schools. Reported 2021 shares: satellite 15.25% primary / 28.57% secondary; unregistered 9.39% / 3.26%.
10. (OFF-01) School Finance Policy bands **P1/S1, P2/S2, P3/S3** reflect density/boarding/revenue and capitation; these are funding categories, not a governance taxonomy.
11. (RES-01) Non-government ownership categories listed by UNESCO (based on 2019 statistics) include church/mission, private company, private individual, farm, trust, mine and other. This is a secondary source and is dated.

**Unresolved**

12. No single official taxonomy was verified that combines ownership, funding band, day/boarding and special-needs status. "Mission", "council", "trust", "private" and "special" must not be presented as one official MoPSE classification on this evidence.

## Topic 3 — multi-school architecture requirements

**Confirmed**

13. (OFF-01, OFF-02, OFF-03) Data flow is genuinely multi-institution: school-level capture → district → province → national. Mother/satellite relationships are a real domain relationship (OFF-01).
14. (OFF-01) The Compact reported that mapping of schools, wards, clusters, mother and satellite schools was **not yet established** at publication.

**Inferences / gaps**

15. The evidence supports a multi-school master-data view and roll-up reporting. It does **not** establish a requirement for tenant isolation, cryptographic separation or a specific deployment topology. Any such design remains **PROPOSED DESIGN** and requires an ADR.
16. (OBS-01) POTRAZ reported inspections/assessments from 1 September 2026 with "schools, tertiary educational institutions and professional bodies" in a listed category. The underlying official regulatory notice was not fetched; treat as RESEARCH-level.
17. (OBS-02) Veritas distinguishes statutory duties from POTRAZ guidance and notes disagreement about whether public entities must obtain controller licences. Recorded as conflict, not resolved.

## Requirement candidates — PROPOSED only

- **PROPOSED PF-001:** Store EMIS code, registration number, name, and ward/district/province; treat code formats as configurable pending official verification (F1–F5).
- **PROPOSED PF-002:** Model Government/non-Government, registration/provisional status, registered/satellite/unregistered status and mother-school link (F1, F9).
- **PROPOSED PF-003:** Support school-level capture and consolidation to district/provincial/national views (F3, F13).
- **PROPOSED PF-004:** Maintain an annual institution master list with duplicate checks (F3, F4).
- **PROPOSED PF-005:** Keep ownership/funding categories configurable rather than hard-coded to one taxonomy (F10–F12).
- **PROPOSED PF-006:** Capture DPO/controller-licence and child-data status fields as metadata, subject to Product Owner decision on applicability (LAW-02, REG-01, OBS-02).

None of these are approved requirements.
