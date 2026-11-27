# Evidence classification

Every finding in this milestone carries one of the following labels. **Labels describe the source, not the importance of the finding.** A PROPOSED DESIGN or ASSUMPTION must never be presented as a confirmed practice or legal requirement.

| Class | Definition | Use in this milestone |
| --- | --- | --- |
| **LEGAL** | Primary statute, regulation, constitutional text or court decision, verified against a statute text or reputable legal repository. | Highest priority for obligations. Note chapter/title discrepancies explicitly. |
| **OFFICIAL** | Government, Ministry (MoPSE), regulator (POTRAZ), ZIMSEC or official institutional publication. | Supports official classifications, procedures and published figures. |
| **DOCUMENTED PRACTICE** | Published document from a real school or association describing how that institution operates. One school = one instance, not a national rule. | Supports candidate workflows; requires stakeholder validation before generalisation. |
| **INTERVIEW VALIDATED** | Statement confirmed by a structured stakeholder interview under a recorded protocol. | **Not used in this milestone. No interviews were conducted.** |
| **RESEARCH** | Peer-reviewed or credible academic/technical research, including international standards bodies where noted. | Supports analysis and external benchmarks; never presented as Zimbabwean law. |
| **PROPOSED DESIGN** | A design idea produced by discovery analysis, explicitly not verified by source or stakeholder. | Used only in product discovery and architecture-implications documents. |
| **ASSUMPTION** | Working belief with no supporting evidence. Must carry an owner and a validation plan. | Recorded as a gap/risk; never promoted to requirement without approval. |

## Source-quality rules

1. Prefer the primary source. A mirror, search-indexed excerpt or secondary reproduction is marked as such and downgraded.
2. Record the **access date** (all sources in this milestone: `2026-11-27`) and a direct URL.
3. If a PDF or page could not be fetched, state that explicitly. Do not quote it as if verified.
4. Where sources disagree, record both in [conflicting-evidence.md](conflicting-evidence.md) rather than choosing silently.
5. Year-specific facts (school calendars, statistics) carry their year; they are not treated as permanent rules.
6. External standards (e.g. NIST) are labelled RESEARCH/OFFICIAL-external and never attributed to Zimbabwean law.

## Promotion rule

SOURCE → FINDING → requirement candidate is allowed only when:
- the source is LEGAL, OFFICIAL, DOCUMENTED PRACTICE or RESEARCH;
- the finding is stated with its source ID; and
- the resulting requirement is still labelled **PROPOSED** until Product Owner approval.

No future implementation or test status may be claimed in this milestone.
