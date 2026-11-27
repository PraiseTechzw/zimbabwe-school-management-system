# GitHub project board guidance

Sprint 0 committed the issue/PR templates, labels and automation config. **A GitHub Project board was not created in Sprint 0** unless separately verified; the configuration below is the recommended manual setup.

## Recommended columns

| Column | Entry criterion | Exit criterion |
| --- | --- | --- |
| Backlog | Issue has a problem statement and label | Evidence and scope are sufficient for research |
| Research | Question needs evidence or an ADR | Decision recorded (ADR or issue comment) |
| Ready | Acceptance criteria and tests are defined | Work started on a branch |
| In Progress | Assignee set, branch exists | PR opened |
| In Review | PR open, CI reported | Approved and merged |
| Done | Merged, verified, docs updated | Archived from active view |

## Labels used by automation and templates

`bug`, `enhancement`, `research`, `documentation`, `security`, `good first issue`, `help wanted`, `dependencies`, `automated`, `ci`, `sprint-0`, `blocked`, `needs-evidence`, `needs-review`.

Colors and descriptions were applied via `gh label create` during Sprint 0 setup; verify with `gh label list` rather than trusting this document.

## Setup steps (if board is absent)

```bash
gh project create --owner PraiseTechzw --title "Zimbabwe School Management System" --format body
# Then, in the GitHub UI: add the columns above and enable the built-in
# "Item closed" → Done workflow.
```

Alternatively use the GitHub UI: Projects → New project → Board → add columns → configure automation.

## Usage rules

- One issue = one deliverable; no "epic" issues that never close.
- Every `In Review` item must link a PR and show check status.
- Research items must link evidence or an ADR before moving to Ready.
