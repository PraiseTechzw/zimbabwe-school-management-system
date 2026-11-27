# Architecture implications — REVIEW ONLY

**Status:** PROPOSED DESIGN IMPLICATIONS. **No architectural change is approved.** Any change to the accepted architecture still requires an ADR in `docs/adr/` and Product Owner approval. This document does not modify the Sprint 0 ADRs.

## Implications identified from evidence

| # | Implication | Evidence | Review status |
| --- | --- | --- | --- |
| AI-01 | School identity is institutional master data with multiple identifiers and status history; it should not be modelled as a single hard-coded name/code pair. | LAW-03, OFF-01, OFF-02, PRAC-08 | Review only; identifier syntax unresolved (U-06) |
| AI-02 | Mother/satellite relationships and responsible-authority relationships are first-class domain relationships, not merely attributes. | OFF-01, OFF-10, PRAC-02 | Review only; cardinality unresolved |
| AI-03 | Academic calendars and examination windows are separate time-variant domains; neither should be code constants. | PRAC-09–PRAC-12, C-07/C-08 | Review only |
| AI-04 | Role, permission and delegation data need effective dates and evidence of authority. | LAW-03 ss65–66, OFF-04, STD-01/02 external | Review only |
| AI-05 | Personal-data obligations (child consent, guardian representation, retention, breach clocks, cross-border assessment) require compliance metadata and auditable decisions. | LAW-02, REG-01/02, OBS-02 | Review only; legal applicability unresolved |
| AI-06 | Least privilege and separation of duties must be representable in the permission model. | OFF-04, STD-01/02 external | Review only; external standards are not Zimbabwean law |
| AI-07 | Institution records must support consolidation to district/provincial/national views, and offline/Excel-style ingestion may be operationally relevant. | OFF-01–OFF-03 | Review only; no integration design approved |
| AI-08 | Configuration variability (stages, streams, departments, sites) argues for metadata-driven options rather than fixed enumerations. | OFF-05/OFF-06, PRAC-03–PRAC-07, C-04/C-05 | Review only; variability not fully evidenced |
| AI-09 | Audit events and compliance evidence need a durable, append-only conceptual treatment. | LAW-02 s24, OFF-04 | Review only; log content/retention unresolved (U-04) |

## Implications from research batch D (review only)

| # | Implication | Evidence | Review status |
| --- | --- | --- | --- |
| AI-10 | Intermittent connectivity/power makes offline-tolerant capture, queued sync and reconciliation relevant to data design; no universal connectivity guarantee is evidenced | OPS-05, OPS-06, OPS-08, OPS-09 | Review only; school-level measurements missing (U-14) |
| AI-11 | Controller/processor roles, written processor guarantees and transfer controls affect how shared services may be introduced | OPS-01 ss18, 28–29 | Review only; applicability fact-specific (U-01/U-05) |
| AI-12 | CDPA requires auditable access boundaries, but does **not** mandate a particular tenant topology | OPS-01 | Review only; any tenancy choice needs an ADR (U-17) |
| AI-13 | Backup, restore testing and incident runbooks need conceptual treatment; RPO/RTO and retention are unconfirmed | OPS-01, OPS-06, OPS-10 | Review only (U-15) |
| AI-14 | School registration, premises, fee approvals and census flow affect the institution lifecycle and validation steps | OPS-02, OPS-03/OPS-04 (snippets), OPS-06, OPS-07 | Review only; original MoPSE PDFs needed (U-16) |
| AI-15 | Census import/export formats and error reports are operationally relevant; no verified national code/format standard was found | OPS-06, OPS-07 | Review only; do not invent formats |

## Explicitly out of scope in this milestone

- No ADR is created, amended or accepted here.
- No database schema, migration, Eloquent model, API contract or UI is proposed as final.
- No choice of tenant model, hosting topology, queue, cache or storage engine is made.
- No Laravel feature is implemented.

## Decision path

Architecture implications become architecture decisions only when the Product Owner accepts the Phase 1 specification (`P1-G01`) and a separate ADR is accepted through the repository's normal process.
