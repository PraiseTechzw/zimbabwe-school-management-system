# Phase 1 research plan — Platform Foundation

**Status:** PROPOSED discovery plan. **Date:** 2026-11-27. **Owner:** Product Owner (approval gate `P1-G01`).

## 1. Purpose

Establish an evidence base for the *Platform Foundation* module before any requirement is approved or code is written. The plan answers: *what is true about Zimbabwean schools, their data and their obligations*, not *what will be built*.

## 2. Scope — the 18 research topics

| # | Topic | Tracking issue |
| --- | --- | --- |
| 1 | School identity and school configuration | `P1-R01` |
| 2 | Zimbabwean school types and institutional structures | `P1-R02` |
| 3 | Multi-school architecture requirements | `P1-R03` |
| 4 | School organisational structures | `P1-R04` |
| 5 | ECD, primary, secondary and A-Level structures | `P1-R05` |
| 6 | Classes, streams, departments and campuses | `P1-R06` |
| 7 | Academic calendars, years and terms | `P1-R07` |
| 8 | User types and stakeholder roles | `P1-R08` |
| 9 | Authentication requirements | `P1-R09` |
| 10 | Role-based access control | `P1-R10` |
| 11 | School-level permissions and delegation | `P1-R11` |
| 12 | Audit trails and accountability | `P1-R12` |
| 13 | Configuration requirements | `P1-R13` |
| 14 | Tenant/data isolation | `P1-R14` |
| 15 | Privacy and applicable Zimbabwe legislation | `P1-R15` |
| 16 | Low-bandwidth and infrastructure considerations | `P1-R16` |
| 17 | Backup, recovery and operational resilience | `P1-R17` |
| 18 | School onboarding and initial configuration workflows | `P1-R18` |
| + | Stakeholder interview programme (future work) | `P1-R19` |

## 3. Source priority (fixed order)

1. Zimbabwe legislation (statutes, regulations, constitutional text).
2. Government and Ministry sources.
3. MoPSE.
4. Official institutional documentation.
5. ZIMSEC where relevant.
6. Verified school documents.
7. Academic research.
8. Stakeholder interviews (**not conducted in this milestone**).
9. Clearly labelled assumptions.

## 4. Method

- **Controlled batches** with disjoint topic sets, so no two researchers edit the same deliverable.
- For each source: record title, publisher, URL, access date, classification and reliability note in [source-register.md](source-register.md).
- For each finding: state the exact claim, attach source ID(s), separate confirmed facts from inferences.
- Record contradictions in [conflicting-evidence.md](conflicting-evidence.md) instead of selecting one silently.
- Convert findings into **requirement candidates** only under an explicit PROPOSED label.
- Maintain [research-coverage-matrix.md](research-coverage-matrix.md) as the authoritative gap view.
- Link SOURCE → FINDING → WORKFLOW → REQUIREMENT → PROPOSED DESIGN in [../modules/platform-foundation/traceability-matrix.md](../modules/platform-foundation/traceability-matrix.md); leave FUTURE IMPLEMENTATION and TEST blank.

## 5. Batches executed

| Batch | Topics | Status |
| --- | --- | --- |
| A — Legal and institutional identity | 1, 2, 3, 15 | Complete: findings 01 and 05 |
| B — Structure and organisation | 4, 5, 6 | Complete: findings 02 |
| C — Calendar, identity and accountability | 7–12 | Complete: findings 03 |
| D — Configuration and operations | 13, 14, 16, 17, 18 | See findings 04; gaps recorded where primary sources were unavailable |

## 6. Non-goals (strict)

No Laravel feature code; no school, user, permission or configuration implementation; no academic calendar implementation; no database migrations for school functionality; no dashboards or UI; no admissions, attendance, finance or academics modules; no architecture changes without an accepted ADR; no assumption promoted to confirmed requirement.

## 7. Deliverables

The documents indexed in [README.md](README.md), plus:

- [../modules/platform-foundation/product-discovery.md](../modules/platform-foundation/product-discovery.md) — PROPOSED product definition.
- [../modules/platform-foundation/traceability-matrix.md](../modules/platform-foundation/traceability-matrix.md) — planned trace links only.
- [../modules/platform-foundation/discovery-report.md](../modules/platform-foundation/discovery-report.md) — comprehensive milestone report.

## 8. Approval gate

The Product Owner must review and explicitly approve the Phase 1 specification before implementation begins (issue `P1-G01`). This milestone ends at the gate; **no implementation is authorised by these documents**.
