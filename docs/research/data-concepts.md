# Proposed data concepts — NOT A SCHEMA

**Status:** PROPOSED DESIGN. These are discovery-level concepts only. No tables, columns, Eloquent models, migrations or code are authorised by this document.

| Concept | Meaning (PROPOSED) | Evidence | Caveat |
| --- | --- | --- | --- |
| Institution / School | The core entity representing one school | LAW-03 s9; OFF-01 | Only statutory split Government/non-Government is verified |
| InstitutionIdentifier | A named identifier (EMIS code, registration number) with source and verification status | PRAC-08, OFF-02 | Syntax unverified (U-06/U-07) |
| InstitutionStatus | Registered / provisional / satellite / unregistered with effective dates | LAW-03 ss15/19; OFF-01 | Satellite lifecycle rules unverified |
| SatelliteRelationship | Mother-school link with authorisation evidence | OFF-01, OFF-10 | Cardinality unverified |
| ResponsibleAuthority | Person/body legally responsible for establishment and management | LAW-03; PRAC-02 | Term differs from informal "board" |
| GoverningBody | SDC, School Parents Assembly, Board of Governors or parent committee with type and constitution | LAW-03 s36; OFF-04; PRAC-01/02 | Composition conflicts (C-02/C-03) |
| AcademicYear | A named year with source, year label and revision history | PRAC-09–PRAC-11 | Dates are year-specific |
| Term | A period within an academic year with counting basis (school days/calendar days) | PRAC-09/10 | Counting conflict C-07 |
| ExaminationWindow | Separate calendar entry from ZIMSEC with source and qualification level | PRAC-12 | O/A-Level source pending |
| Stage / GradeForm | ECD A/B, Grades 1–7, Forms 1–4, Lower 6, Upper 6 with explicit mapping metadata | OFF-05, OFF-09 | Crosswalk unverified (U-09) |
| Class / Stream / Department / Site / Hostel | Optional organisational units under a school; stream carries explicit semantics | PRAC-03–PRAC-07 | Meanings vary (C-05) |
| RoleAssignment | A role held by a person with effective dates and source of authority | OFF-04, OFF-10, LAW-03 | No national role model (U-11) |
| Permission | A named action boundary assigned through roles, not directly to individuals by default | STD-01/02 external | External benchmark only |
| Delegation | Time-bounded, revocable grant with delegator, delegate, scope and approval evidence | LAW-03 ss65–66; OFF-04 | Delegation limits unverified |
| PersonalDataRequest | Access/correction/deletion request with child/guardian representation evidence | LAW-02 ss13–14, 26 | Deadlines partly unverified |
| BreachCase | Awareness time, affected categories, decisions and notifications | LAW-02 s20; REG-01 s17 | 72h claim unverified (C-11) |
| ComplianceEvidence | DPO/licence status, retention schedule, cross-border assessment | LAW-02; REG-01; OBS-02 | Applicability unresolved (U-01/U-05) |
| AuditEvent | An immutable record of a significant action; content undecided | LAW-02 s24; OFF-04; STD-01 external | U-04 |
| SourceReference | URL, publisher, access date, classification and reliability note attached to a finding or configuration value | Evidence classification rules | Internal discovery concept |

## Design constraints (PROPOSED, review-only)

1. School is the only mandatory organisational anchor; all lower units are optional until evidenced.
2. Time-variant facts (status, roles, delegations, calendars, identifiers) carry effective dates and source references.
3. Conflicting values are not silently overwritten; source and classification remain visible.
4. No data concept here implies a storage model, identifier generation strategy or database migration.

Architecture implications are reviewed separately in [architecture-implications.md](architecture-implications.md). No architecture change is approved.
