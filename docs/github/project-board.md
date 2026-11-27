# GitHub project board — verified configuration

**Verified:** 2026-11-27. **Status:** ACTIVE. Sprint 0's "recommended manual setup" below has been replaced with the verified configuration.

**Project:** [#8 — Zimbabwe School Management System](https://github.com/users/PraiseTechzw/projects/8)
**Node ID:** `PVT_kwHOCVYfs84BtfBW` · **Linked repository:** `PraiseTechzw/zimbabwe-school-management-system` · Default "View 1" deleted.

## Status workflow

| Status | Option ID | Entry criterion | Exit criterion |
| --- | --- | --- | --- |
| Research Backlog | `dbaa8876` | Question stated with an evidence plan | Findings recorded with source IDs |
| Product Backlog | `f519a1e2` | Deliverable/decision scoped; not yet Ready | Acceptance criteria and evidence sufficient for Ready |
| Ready | `d4978aa1` | Product Owner approval recorded | Work started on a branch |
| In Progress | `8b072825` | Assignee and branch exist | PR opened |
| Review | `844f84ae` | PR open and CI reported | Approved and merged |
| Done | `c2c39d68` | Merged, verified, docs updated | Archived from active views |

**Phase 1 rule:** no item was moved to Ready, In Progress, Review or Done. `P1-G01` (approval gate) must be resolved first.

## Fields

| Field | ID | Configuration |
| --- | --- | --- |
| Status | `PVTSSF_lAHOCVYfs84BtfBWzhroS_0` | Six options above |
| Priority | `PVTSSF_lAHOCVYfs84BtfBWzhroTuE` | P0, P1, P2, P3 |
| Module | `PVTSSF_lAHOCVYfs84BtfBWzhroTvo` | Platform Foundation, Academics, Admissions, Attendance, Finance, Reporting, HR, Boarding, Cross-Cutting |
| Work Type | `PVTSSF_lAHOCVYfs84BtfBWzhroTwE` | Research, Feature, Bug, Documentation, Infrastructure, Testing, Decision, Security |
| Evidence Classification | `PVTSSF_lAHOCVYfs84BtfBWzhroTxg` | LEGAL, OFFICIAL, DOCUMENTED PRACTICE, INTERVIEW VALIDATED, RESEARCH, PROPOSED DESIGN, ASSUMPTION |
| Blocked | `PVTSSF_lAHOCVYfs84BtfBWzhroT30` | No / Yes - evidence needed / Yes - decision needed |
| Milestone | `PVTF_lAHOCVYfs84BtfBWzhroTAA` | Built-in field; GitHub rejects a custom field named "Milestone" as reserved |

## Milestones

| Milestone | Number | Scope |
| --- | --- | --- |
| Sprint 0 | 1 | Engineering foundation |
| Sprint 0.1 | 2 | Verification corrections |
| Phase 1 - Platform Foundation | 3 | 27 discovery issues (`P1-R*`, `P1-D*`, `P1-G*`) |

## Views

| View | Layout | Filter |
| --- | --- | --- |
| Main Kanban | BOARD_LAYOUT | none |
| Research | BOARD_LAYOUT | `"Work Type":Research` |
| Backlog | TABLE_LAYOUT | `status:"Research Backlog" OR status:"Product Backlog"` |
| Roadmap | ROADMAP_LAYOUT | `milestone:"Phase 1 - Platform Foundation"` |
| Active Work | BOARD_LAYOUT | `status:"In Progress" OR status:"Review"` |
| Risks and Decisions | TABLE_LAYOUT | `"Work Type":Decision OR "Work Type":Security OR "Blocked":"Yes - evidence needed"` |

Filters were read back via GraphQL after creation. UI rendering has not been independently checked in a browser.

## Labels in use

`bug`, `enhancement`, `research`, `documentation`, `security`, `good first issue`, `help wanted`, `dependencies`, `automated`, `ci`, `sprint-0`, `blocked`, `needs-evidence`, `needs-review`, plus `phase-1` and `discovery` added for this milestone. Verify with `gh label list --repo PraiseTechzw/zimbabwe-school-management-system` rather than trusting this document.

## Usage rules

- One issue = one deliverable; no "epic" issues that never close.
- Every `In Review` item must link a PR and show check status.
- Research items must link evidence (source IDs and classification) before moving to Ready.
- Evidence-gated items set `Blocked: Yes - evidence needed`; decision-gated items set `Yes - decision needed`.
- No implementation item may move to Ready before `P1-G01` approval.

## Governance decision — solo-maintainer branch protection (issue `P1-G02`)

**Decision recorded:** 2026-11-27. **Status:** ACTIVE, interim; ratification pending on `P1-G02`.

**Context.** PR #1 could not be merged while `main` required one approving review: the repository's only collaborator is the owner, and GitHub rejects self-approval (`Review Can not approve your own pull request`). All CI checks had passed. The repository also disallows merge commits (squash only).

**Action taken at the user's instruction.** Required approving reviews on `main` was changed from 1 to **0** so an automated merge could proceed. The following protections were **retained**:

- Required status checks: `quality`, `tests`, `docker`.
- `enforce_admins` enabled.
- Conversation resolution required.
- Force pushes and branch deletion disabled.

**Rationale.** Interim workaround for a solo-maintainer repository; CI gates remain the enforceable quality barrier. PR #1 was subsequently merged by the user via squash as `ffa2f3b`.

**Exit criterion.** Restore `required approving reviews = 1` as soon as a second reviewer/collaborator is available. Product Owner must ratify or revert this change on `P1-G02`. No other branch-protection rule was relaxed.

## Verification commands

```bash
gh project view 8 --owner PraiseTechzw
gh project field-list 8 --owner PraiseTechzw --format json
gh api repos/PraiseTechzw/zimbabwe-school-management-system/milestones \
  --jq '.[] | {number,title}'
gh issue list --repo PraiseTechzw/zimbabwe-school-management-system \
  --milestone "Phase 1 - Platform Foundation" --state all
```
