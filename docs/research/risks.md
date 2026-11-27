# Risks — Phase 1 discovery

**Status:** LIVE REGISTER. Risk likelihood/impact are qualitative discovery assessments, not measured probabilities.

| ID | Risk | Evidence | Likelihood | Impact | Mitigation / owner | Status |
| --- | --- | --- | --- | --- | --- | --- |
| R-01 | Primary sources (Education Act, SDC Handbook, MoPSE statistics, ZIMSEC forms) were only accessible as indexed excerpts; findings may rest on misquoted text. | Source register "Indexed" notes | High | High | Re-fetch originals before approval; mark affected candidates as PROPOSED. Research owner | Open |
| R-02 | School-specific documented practice (private/urban schools) is generalised to all Zimbabwean schools. | PRAC-03–PRAC-07 | High | Medium | Multi-school sample and stakeholder interviews (`P1-R19`). Product Owner | Open |
| R-03 | Hard-coding year-specific term dates or exam windows will break annually; fake calendars circulate. | PRAC-09–PRAC-12; C-07/C-08 | Medium | High | Configurable academic years with source/year metadata (PF-030, PF-031). Architecture review | Open |
| R-04 | Mislabelling SDC/Responsible Authority as a "board" or assigning signing powers without legal delegation creates governance challenges. | LAW-03, OFF-04, PRAC-02 | Medium | High | Validate per-school governance; keep delegation records configurable (PF-035). Legal review | Open |
| R-05 | Learner records combine child, educational, health/disability and family data — high sensitivity. | LAW-02 s3; REG-02 | High | High | Privacy by design; data minimisation; guardian-rights workflow (PF-020–PF-027). Product Owner | Open |
| R-06 | 24-hour breach clock and possible licence/DPO obligations may apply to schools from 2026. | LAW-02 s20; REG-01; OBS-01/02 | Medium | High | Resolve U-01/U-02; record DPO/licence metadata (PF-024, PF-025). Legal review | Open |
| R-07 | Ambiguous school identifiers corrupt roll-ups and inter-ministry comparisons. | OFF-02; C-13; U-06/U-07 | Medium | High | Do not define canonical codes until MoPSE source obtained; duplicate checks (PF-004). Research owner | Open |
| R-08 | Satellite/unregistered schools are under-mapped, risking exclusion or duplicate records. | OFF-01, OFF-02 | Medium | Medium | Model mother/satellite relationships and registration status (PF-002). Product Owner | Open |
| R-09 | Treating NIST or other external standards as Zimbabwean law would misstate compliance obligations. | STD-01–STD-04 | Medium | Medium | Label external benchmarks RESEARCH (external) in all documents. Documentation owner | Open |
| R-10 | Discovery converts assumptions into approved requirements without evidence. | Evidence classification rules | Medium | High | Keep gate `P1-G01` closed; all candidates labelled PROPOSED. Product Owner | Open |
| R-11 | No stakeholder interviews were conducted; INTERVIEW VALIDATED evidence is absent. | Interview programme `P1-R19` | Certain | Medium | Schedule interviews; never fabricate interview findings. Research owner | Open |
| R-12 | Repository governance change (required approvals = 0) weakens human review. | Verified branch-protection change on 2026-11-27 | Certain | Medium | Keep mandatory CI gates (`quality`, `tests`, `docker`); revisit when a second reviewer exists (issue `P1-G02`). Product Owner | Accepted / monitored |
| R-13 | Assuming continuous connectivity risks data loss and exclusion of rural schools; 2019 constraints and 2025 national penetration figures describe different periods. | OPS-05, OPS-06, OPS-08, C-15 | Medium | High | Offline-tolerant candidates PF-044/PF-045, NFR-017–019, pending U-14. Architecture review | Open |
| R-14 | Weak access separation or missing processor governance risks CDPA breach exposure. | OPS-01 s18 | Medium | High | PF-042/PF-043, NFR-020/021; resolve U-01/U-05; ADR for tenancy. Legal review | Open |
| R-15 | Index-only MoPSE statistical evidence may misstate onboarding requirements. | OPS-03/OPS-04 | High | Medium | Obtain original PDFs before relying on exact extracts (U-16). Research owner | Open |
| R-16 | ADEA's 2019 infrastructure findings may be stale against POTRAZ's 2025 growth figures. | OPS-05 vs OPS-06, C-15 | High | Medium | Do not set numeric infrastructure targets from either alone; validate with interviews. Product Owner | Open |

## Risk-to-question map

R-01 → U-01…U-05; R-02 → U-10…U-12; R-03 → U-08/U-09; R-04 → U-11; R-05/R-06 → U-01/U-02/U-05; R-07 → U-06/U-07; R-08 → U-16; R-10 → U-19; R-13 → U-14; R-14 → U-01/U-05/U-17; R-15 → U-16; R-16 → U-14.
