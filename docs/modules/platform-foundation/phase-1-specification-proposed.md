# Phase 1 specification — PROPOSED FOR PRODUCT OWNER APPROVAL

> **Post-discovery update (2026-11-27):** this specification is retained as the discovery-time proposal. The Product Owner subsequently released `P1-G01` for execution (D-08 in [decision-log.md](decision-log.md)); the decision log, not this document, records what was implemented.

**Status:** PROPOSED. **Approval gate:** issue `P1-G01` (open at the time of this proposal). **Date:** 2026-11-27.

This document is the candidate specification presented to the Product Owner. **It does not authorise implementation.** Approval must be explicit and recorded on `P1-G01` before any item moves to Ready.

## 1. Purpose

Define the evidence-backed foundation for describing Zimbabwean schools, their structure, authority relationships and data obligations, so that future work does not hard-code unverified practices.

## 2. What is being proposed

| Area | Candidate IDs (PROPOSED) | Evidence summary |
| --- | --- | --- |
| School identity and status | PF-001–PF-006, PF-010, PF-018, PF-040, PF-041, PF-048, PF-049 | Education Act registration duties; MoPSE EMIS master list, ED 46 census and school→district→HQ flow; satellite/mother-school relationship |
| Organisational structure and calendars | PF-011–PF-017, PF-030–PF-032 | MoPSE stage nomenclature, UNESCO structure summary, varied school documents, year-specific calendars, ZIMSEC levels |
| Privacy and data-subject processes | PF-020–PF-027, PF-043 | Cyber and Data Protection Act [Cap 12:07], S.I. 155, POTRAZ children's-data guideline |
| Permissions, delegation, audit, resilience | PF-033–PF-039, PF-042, PF-044–PF-047 | Education Act delegation, SDC Handbook documented separation of duties, NIST external benchmarks, POTRAZ statistics, UNICEF/MoPSE guidance |

Full tables: [../../research/requirement-candidates.md](../../research/requirement-candidates.md), [../../research/non-functional-requirements.md](../../research/non-functional-requirements.md). Trace links: [traceability-matrix.md](traceability-matrix.md).

## 3. Evidence standard applied

- [../../research/evidence-classification.md](../../research/evidence-classification.md): LEGAL > OFFICIAL > DOCUMENTED PRACTICE > RESEARCH > PROPOSED DESIGN > ASSUMPTION.
- Every candidate has at least one source ID.
- Conflicts are recorded in [../../research/conflicting-evidence.md](../../research/conflicting-evidence.md); no silent selection.
- Gaps are recorded in [../../research/unresolved-questions.md](../../research/unresolved-questions.md) and [../../research/research-coverage-matrix.md](../../research/research-coverage-matrix.md).
- No interviews were conducted; INTERVIEW VALIDATED evidence is empty by design.

## 4. Decisions requested from the Product Owner

| # | Decision | Why it is needed | Options |
| --- | --- | --- | --- |
| D-01 | Approve or reject the PROPOSED candidate set as the Phase 1 specification | Gate before any implementation | Approve / approve with exclusions / reject |
| D-02 | Confirm which candidates are in Phase 1 scope versus deferred | Many candidates depend on unresolved evidence | Select scope |
| D-03 | Decide whether public schools are in scope for controller licensing/DPO metadata (PF-006, PF-025) | Legal applicability unresolved (U-01) | Include conditionally / defer pending legal opinion |
| D-04 | Decide the tenancy review path (PF-042) | Evidence does not mandate a topology (U-17) | Open an ADR / defer |
| D-05 | Approve NFR targets for offline operation and resilience (PF-044–PF-047) | No numeric targets are evidenced (U-14/U-15) | Set targets with stakeholder input / defer |
| D-06 | Confirm the stakeholder interview programme (`P1-R19`) for the next milestone | No interviews conducted; many gaps need validation | Approve / defer |
| D-07 | Confirm the solo-maintainer governance change record (`P1-G02`) | Required approvals = 0 on `main`; CI gates retained | Ratify / revert |
| D-08 | Confirm that no implementation begins until D-01 is recorded on `P1-G01` | Core governance rule | Confirm |

## 5. Acceptance criteria for approval

1. D-01 is recorded as an explicit comment/close on `P1-G01` with the approved candidate IDs.
2. Any rejected or deferred candidate is marked accordingly in [requirement-candidates.md](../../research/requirement-candidates.md).
3. Conflicts C-01–C-16 remain visible; any resolution cites a new source or a recorded decision.
4. Every approved item has an approved source classification; ASSUMPTION-only items are not approved.
5. Future implementation and test links in [traceability-matrix.md](traceability-matrix.md) are filled only after approval, in a later milestone.

## 6. Explicitly excluded from this specification

Admissions, attendance, finance transactions, academics/assessment, reporting products, implemented learner records, HR, boarding operations, dashboards/UI, Laravel feature code, school/user/permission implementation, academic-calendar implementation, school-specific migrations, and architecture changes without an accepted ADR.

## 7. Approval record

| Field | Value |
| --- | --- |
| Product Owner decision | **PENDING** |
| Date | — |
| Approved candidate IDs | — |
| Deferred/rejected IDs | — |
| Gate issue | `P1-G01` — open |
| Implementation authorised | **No** |
