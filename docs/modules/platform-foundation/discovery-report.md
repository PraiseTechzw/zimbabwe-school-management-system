# Phase 1 Discovery Report — Platform Foundation

> **Post-discovery update (2026-11-27):** this report records the state at the end of the discovery milestone. The Product Owner subsequently released `P1-G01` for execution (D-08 in [decision-log.md](decision-log.md)) and all seven foundation slices merged into `main` via PRs #41, #43, #44, #45, #49, #50 and #51; the later Academic Structure backend foundation merged in #59. See the decision log for current implementation status.

**Date:** 2026-11-27. **Status:** COMPLETE FOR PRODUCT OWNER REVIEW (discovery milestone). **Implementation:** NOT AUTHORISED *at the time of this report*.

## 1. Executive summary

Phase 1 established the GitHub Project board, created 27 discovery issues, completed research across all 18 required topics, and produced an evidence-classified documentation set for the Platform Foundation module. The work stopped at the Product Owner approval gate (`P1-G01`).

**Key conclusion:** the evidence base is sufficient to define **PROPOSED** requirement candidates, but **not** sufficient to approve implementation requirements. Four clusters remain assumption-only (identifier syntax, tenancy topology, code/class formats, RPO/RTO) and no stakeholder interviews were conducted.

## 2. Repository and branch state

| Item | Verified value |
| --- | --- |
| Repository | `PraiseTechzw/zimbabwe-school-management-system` |
| `main` head | `ffa2f3b` — Squashed PR #1, Sprint 0 foundation |
| Work branch | `docs/phase-1-discovery` (created from updated `main`) |
| Discovery commit | Committed locally on `docs/phase-1-discovery`; not pushed to `main` |
| Preserved user work | Stash `sprint-0.1-verification-corrections-preserve`; same corrections also committed by the user on `docs/sprint-0-report-corrections` |
| Code changes | **None** — no Laravel feature, migration, UI, permission, calendar or architecture change |

## 3. GitHub Project board — verified configuration

**Project:** [#8 — Zimbabwe School Management System](https://github.com/users/PraiseTechzw/projects/8)
**Node ID:** `PVT_kwHOCVYfs84BtfBW` · **Repository link:** verified · **Default "View 1":** deleted.

### Status options

| Status | Option ID | Entry meaning used by this milestone |
| --- | --- | --- |
| Research Backlog | `dbaa8876` | Research task with question and evidence plan |
| Product Backlog | `f519a1e2` | Documentation/decision deliverable not yet Ready |
| Ready | `d4978aa1` | Reserved for post-approval work — **no item moved here** |
| In Progress | `8b072825` | Reserved for active work |
| Review | `844f84ae` | Reserved for review |
| Done | `c2c39d68` | Completed and verified |

### Fields

| Field | ID | Options / notes |
| --- | --- | --- |
| Priority | `PVTSSF_lAHOCVYfs84BtfBWzhroTuE` | P0, P1, P2, P3 |
| Module | `PVTSSF_lAHOCVYfs84BtfBWzhroTvo` | Platform Foundation, Academics, Admissions, Attendance, Finance, Reporting, HR, Boarding, Cross-Cutting |
| Work Type | `PVTSSF_lAHOCVYfs84BtfBWzhroTwE` | Research, Feature, Bug, Documentation, Infrastructure, Testing, Decision, Security |
| Evidence Classification | `PVTSSF_lAHOCVYfs84BtfBWzhroTxg` | LEGAL, OFFICIAL, DOCUMENTED PRACTICE, INTERVIEW VALIDATED, RESEARCH, PROPOSED DESIGN, ASSUMPTION |
| Blocked | `PVTSSF_lAHOCVYfs84BtfBWzhroT30` | No / Yes - evidence needed / Yes - decision needed |
| Milestone | `PVTF_lAHOCVYfs84BtfBWzhroTAA` | Built-in field (a custom field named "Milestone" is rejected by GitHub as a reserved name) |

### Milestones

| Milestone | Number |
| --- | --- |
| Sprint 0 | 1 |
| Sprint 0.1 | 2 |
| Phase 1 - Platform Foundation | 3 |

### Views

| View | Layout | Filter |
| --- | --- | --- |
| Main Kanban | BOARD_LAYOUT | none |
| Research | BOARD_LAYOUT | `"Work Type":Research` |
| Backlog | TABLE_LAYOUT | `status:"Research Backlog" OR status:"Product Backlog"` |
| Roadmap | ROADMAP_LAYOUT | `milestone:"Phase 1 - Platform Foundation"` |
| Active Work | BOARD_LAYOUT | `status:"In Progress" OR status:"Review"` |
| Risks and Decisions | TABLE_LAYOUT | `"Work Type":Decision OR "Work Type":Security OR "Blocked":"Yes - evidence needed"` |

Filters were verified by reading back the GraphQL query strings. UI rendering was not independently verified in a browser.

### Issues created (27, all in milestone 3 and on project #8)

| Group | Issues | GitHub numbers |
| --- | --- | --- |
| Research topics | `P1-R01`–`P1-R19` | 6–24 |
| Discovery deliverables | `P1-D01`–`P1-D06` | 25–30 |
| Decision gates | `P1-G01`, `P1-G02` | 31–32 |

Labels used: `research`, `needs-evidence`, `phase-1`, `documentation`, `needs-review`. Research items are in **Research Backlog**; deliverables and gates are in **Product Backlog**. Evidence-gated items are marked `Blocked: Yes - evidence needed` or `Yes - decision needed`. No item is in Ready, In Progress, Review or Done.

## 4. Governance decision — branch protection

PR #1 could not be merged initially because `main` required one approving review; the repository's only collaborator is the owner, and GitHub rejects self-approval. With the user's instruction to merge automatically:

- Required approving reviews on `main` was set to **0**.
- Retained: required status checks (`quality`, `tests`, `docker`), `enforce_admins`, conversation resolution, no force pushes, no branch deletion.
- PR #1 was merged by the user via squash as `ffa2f3b`.
- Rationale must be recorded on `P1-G02`: temporary solo-developer governance measure; restore `required approving reviews = 1` when a second reviewer exists.
- Current documentation at [../../github/project-board.md](../../github/project-board.md) still describes the pre-board recommended setup and must be replaced with the verified configuration above (done in this milestone).

## 5. Research execution

| Batch | Topics | Issues | Output |
| --- | --- | --- | --- |
| A — Legal and institutional identity | 1, 2, 3, 15 | `P1-R01`–`P1-R03`, `P1-R15` | Findings 01, 05 |
| B — Structure and organisation | 4, 5, 6 | `P1-R04`–`P1-R06` | Findings 02 |
| C — Calendar, identity and accountability | 7–12 | `P1-R07`–`P1-R12` | Findings 03 |
| D — Configuration and operations | 13, 14, 16, 17, 18 | `P1-R13`, `P1-R14`, `P1-R16`–`P1-R18` | Findings 04 |
| Interview programme (planned, not executed) | + | `P1-R19` | Interview guide only |

Priority order was enforced: Zimbabwe legislation → Government/MoPSE → official institutional documentation → ZIMSEC → verified school documents → academic research → stakeholder interviews (none) → labelled assumptions.

### Coverage summary

| Evidence class | Topics with at least one source |
| --- | --- |
| LEGAL | 13 of 18 |
| OFFICIAL | 15 of 18 |
| DOCUMENTED PRACTICE | 9 of 18 |
| RESEARCH | 12 of 18 |
| INTERVIEW VALIDATED | **0** |
| Assumption-only clusters | 4 |

Full matrix: [../../research/research-coverage-matrix.md](../../research/research-coverage-matrix.md). **No topic is fully closed.**

## 6. Principal findings (all sourced; see source register)

1. **Education Act [Cap 25:04]** classifies schools as Government/non-Government, requires registration and records, establishes the School Parents Assembly and SDC, and permits conditional, withdrawable delegation to Ministry officials (LAW-03, OPS-02).
2. **MoPSE EMIS** uses the ED 46 annual census and school → district → province → HQ flow; master-list and code-compatibility problems were reported in 2019 (OFF-01, OFF-02, OFF-06, OPS-07).
3. **Privacy law** is the Cyber and Data Protection Act [Cap 12:07], No. 5 of 2021 (corrected republication GN 492/2022), with security duties, 24-hour breach notification, data-subject rights, child representation and cross-border controls; S.I. 155 adds licensing/DPO tiers (LAW-02, REG-01, OPS-01).
4. **School structures vary**: official stage names (ECD A/B, Grades 1–7, Forms 1–4, Lower/Upper 6) coexist with school-specific departments, streams and governance arrangements; "stream" has two documented meanings (OFF-05/06/09, PRAC-03–07).
5. **Calendars are year-specific**; a 2026 "fake calendar" incident and 2025/2026 date differences show dates must be data with source metadata, not constants (PRAC-09–11).
6. **Documented finance separation of duties** exists in the SDC Handbook (Head initiates, SDC Chair certifies/approves, joint signing, unanimous subcommittee decisions); this is official guidance, not a verified universal legal rule (OFF-04).
7. **Infrastructure evidence spans periods**: POTRAZ 2025 reports 81.83% Internet/data penetration and mobile penetration 102.64%, while a 2019 peer review found manual ED 46 completion; UNICEF deployed offline servers in 113 schools (OPS-05, OPS-06, OPS-08).
8. **No source was found** for identifier syntax, class/code formats, IT audit-log retention, backup frequency/RPO/RTO or a mandated tenancy topology. These are explicit gaps, not inferred requirements.

## 7. Conflicts, gaps and risks

- **16 documented conflicts** (C-01–C-16), including privacy-law citation, SDC composition, PTA vs School Parents Assembly, Grade/Form crosswalk, stream semantics, ZJC status, calendar day counts, licensing applicability, breach notification to individuals, and a POTRAZ internal percentage mismatch.
- **19 unresolved questions** (U-01–U-19) requiring higher-priority sources, legal opinion, Product Owner decision or interviews.
- **16 risks** (R-01–R-16), led by indexed-only primary sources, generalising school-specific practice, hard-coded dates, governance mislabelling, child-data sensitivity, identifier ambiguity and no interviews.
- No conflict was resolved silently; each has both positions and an action.

## 8. Deliverables produced on `docs/phase-1-discovery`

| Deliverable | Path | Issue |
| --- | --- | --- |
| Research index | `docs/research/README.md` | — |
| Research plan | `docs/research/phase-1-research-plan.md` | `P1-D01` |
| Evidence classification | `docs/research/evidence-classification.md` | — |
| Source register (~50 sources) | `docs/research/source-register.md` | `P1-D01` |
| Findings 01–05 | `docs/research/findings/` | `P1-D02` |
| Conflicts | `docs/research/conflicting-evidence.md` | `P1-D02` |
| Unresolved questions | `docs/research/unresolved-questions.md` | `P1-D02` |
| Risks | `docs/research/risks.md` | `P1-D03` |
| Coverage matrix | `docs/research/research-coverage-matrix.md` | `P1-D03` |
| Proposed workflows WF-01–WF-08 | `docs/research/proposed-workflows.md` | `P1-D04` |
| Interview questions | `docs/research/stakeholder-interview-questions.md` | `P1-R19` |
| Requirement candidates (~45) | `docs/research/requirement-candidates.md` | `P1-D04` |
| NFR candidates (~23) | `docs/research/non-functional-requirements.md` | `P1-D04` |
| Data concepts (not a schema) | `docs/research/data-concepts.md` | — |
| Architecture implications (review only) | `docs/research/architecture-implications.md` | — |
| Product discovery | `docs/modules/platform-foundation/product-discovery.md` | `P1-D04` |
| Proposed specification | `docs/modules/platform-foundation/phase-1-specification-proposed.md` | `P1-G01` |
| Traceability matrix | `docs/modules/platform-foundation/traceability-matrix.md` | `P1-D05` |
| This report | `docs/modules/platform-foundation/discovery-report.md` | `P1-D06` |
| Verified board configuration | `docs/github/project-board.md` | — |
| Governance decision record | `docs/github/project-board.md` (§ governance) | `P1-G02` |

## 9. Verification performed

- Repository state checked against GitHub before any action; initial mismatch (unmerged PR #1, no board) was reported and work stopped until resolved.
- PR #1 merge verified locally and on GitHub; CI checks passed.
- Project fields, milestones and all six views were created and read back via `gh`/GraphQL.
- All 27 issues were created, milestones applied and items added to project #8 with Status, Priority, Module, Work Type, Evidence Classification and Blocked set where practical.
- No `Ready`/`In Progress`/`Review`/`Done` transitions were made; no implementation artefacts were created.

## 10. Next task — recommendation

**Immediate next task: Product Owner approval gate `P1-G01`.** The Product Owner must:

1. Review [phase-1-specification-proposed.md](phase-1-specification-proposed.md) and decide D-01–D-08 explicitly.
2. Record the decision on `P1-G01` and mark approved/deferred/rejected candidate IDs.
3. Ratify or revert the solo-maintainer branch-protection change on `P1-G02`.
4. Approve `P1-R19` (stakeholder interviews) and legal review of U-01–U-05 for the next milestone.

**Only after that decision** may implementation items move from Product Backlog to Ready, and any architecture choice must go through a separate accepted ADR. Until then, this repository remains documentation-only for Phase 1.

## 11. Approval statement

This report records discovery, not delivery. **No requirement in it is approved. No implementation is authorised.**
