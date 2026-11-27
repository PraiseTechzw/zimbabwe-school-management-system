# Findings 05 — privacy and applicable Zimbabwe legislation (topic 15)

**Status:** EVIDENCE-IN-PROGRESS. Legal claims below are sourced; requirement candidates are **PROPOSED** and require Product Owner approval plus legal review before implementation. Access date: `2026-11-27`.

## Source IDs used here

| ID | Source | Class |
| --- | --- | --- |
| LAW-01 | Constitution of Zimbabwe, consolidated to 20 June 2023 (Veritas) | LEGAL |
| LAW-02 | Cyber and Data Protection Act [Chapter 12:07], No. 5 of 2021 — corrected republication GN 492/2022 (Veritas) | LEGAL |
| LAW-03 | Education Act [Cap 25:04], ss 23, 36, 65–66 | LEGAL |
| REG-01 | S.I. 155 of 2024 — controller licensing, DPOs, security, breach reporting (mirror) | LEGAL (mirror) |
| REG-02 | POTRAZ guideline: Processing Children's Personal Information (Veritas-hosted copy) | OFFICIAL (guideline) |
| OBS-01 | Veritas Bill Watch 27/2026 — POTRAZ inspections | RESEARCH |
| OBS-02 | Veritas Bill Watch 29/2026 — CDPA implications | RESEARCH |

## Confirmed legal position

1. (LAW-01) Constitution **s57** protects privacy, including non-disclosure of health conditions; **s75** protects basic State-funded education and independent educational institutions (s75(2)–(3)).
2. (LAW-02) The correct current citation is **Cyber and Data Protection Act [Chapter 12:07], No. 5 of 2021**. The Act was first gazetted on 3 December 2021 with an incorrect title/chapter and republished with corrections by GN 492/2022 on 11 March 2022. Older PDFs citing "Data Protection Act [Cap 11:22]" are superseded in title.
3. (LAW-02) CDPA **s3** defines a child as a person under 18 and personal information broadly, including identifying numbers and educational, financial, criminal and employment history.
4. (LAW-02) Key verified provisions: **s2** cyber-security objective; **s4** automated processing; **s5–6** POTRAZ designation and powers; **s10–12** consent; **s13–14** controller/processor duties and data-subject rights; **s18–20** security, breach notification and DPO notification; **s21(3)** inspections for specific risks; **s25–27** automated decisions and representation for children/incapacity; **s28–29** cross-border transfers; **s33** offences.
5. (LAW-02, OBS-02) Section **18(4)** requires "appropriate technical and organisational measures" against unauthorised destruction, loss, alteration, access or processing; s18(6) allows the Authority to issue security standards. Section **13(g)** requires retention no longer than necessary; **s24** requires internal mechanisms to demonstrate compliance.
6. (LAW-02, REG-01) Breach notification to POTRAZ is within **24 hours** (s20; REG-01 s17). A secondary legal analysis (OBS-02 via DLA Piper summary in batch C) reports **72 hours** for notifying affected individuals where high risk; this is RESEARCH-level and needs statutory confirmation.
7. (REG-01) S.I. 155 of 2024 requires a **controller licence** (s4), sets tiers by data-subject count (50–1,000; 1,001–100,000; 100,001–500,000; >500,000) in s6, provides exemptions (s8), requires a **DPO** (s12), security measures (s16) and 24-hour breach reporting (s17). The text was obtained from a university mirror, not an official host.
8. (REG-02) POTRAZ children's-data guideline states children hold s57 privacy rights and s14 CDPA rights, s26 requires guardian exercise of rights and written guardian consent, and promotes privacy by design/default. The guideline is explicitly **not law** (see OBS-02).
9. (LAW-03) Education Act s23 requires record-keeping and information provision by schools; s65 allows conditional, withdrawable delegation to Ministry officials with written Ministerial approval; s66 permits inspection of school accounts and records.
10. (OBS-01) Veritas reports POTRAZ inspections/assessments from **1 September 2026**, listing schools among the categories. The underlying regulatory notice was not fetched; this is a secondary legal report.

## Conflicts and qualifications

- **Citation conflict:** older sources use "Data Protection Act [Cap 11:22]"; corrected republication uses "Cyber and Data Protection Act [Cap 12:07]". Use the corrected citation; record legacy references when quoting older documents.
- **Licensing applicability:** REG-01/OBS-02 indicate licensing obligations, but Veritas (OBS-02) disputes the view that Ministries/public entities must licence. Whether a given public school is a licensed controller is unresolved.
- **Guideline vs law:** POTRAZ guidance on licensing and children's data is not itself legally binding (OBS-02); only statutory duties should be called legal requirements.
- **72-hour individual notification:** reported in secondary analysis only; not verified from the statute text in this batch.

## Risks

- Schools explicitly appear in a reported 2026 inspection wave; evidence of DPO, licence, security and breach process may be requested.
- Learner records combine child, educational, health/disability and family data — high sensitivity under LAW-02 and REG-02.
- Guardian identity proofing is a legal concern where a child exercises data rights (LAW-02 s26; REG-02).
- No Zimbabwean statute was found prescribing IT audit-log contents or retention periods; treating an external standard as law would be incorrect.

## Requirement candidates — PROPOSED only

- **PROPOSED PF-020:** Record lawful basis/consent and guardian consent evidence for child data (LAW-02 ss10–12, 26; REG-02).
- **PROPOSED PF-021:** Support data-subject access, correction and deletion workflows with representation rules for children (LAW-02 ss13–14, 26).
- **PROPOSED PF-022:** Retention controls tied to "no longer than necessary" with configurable, approved retention schedules (LAW-02 s13(g)).
- **PROPOSED PF-023:** Security measures appropriate to risk, with documented organisational and technical controls (LAW-02 s18(4)).
- **PROPOSED PF-024:** Breach workflow capturing awareness time to support the 24-hour POTRAZ deadline; 72-hour individual notification held as RESEARCH pending verification (LAW-02 s20; OBS-02).
- **PROPOSED PF-025:** Store DPO and controller-licence status as metadata; applicability decision required from Product Owner/legal review (REG-01, OBS-02).
- **PROPOSED PF-026:** Cross-border transfer assessment fields and approvals (LAW-02 ss28–29).
- **PROPOSED PF-027:** Audit/accountability evidence sufficient to demonstrate compliance (LAW-02 s24; LAW-03 s23), with log content still to be decided.

None of these are approved requirements.
