# Research coverage matrix — Phase 1

**Status:** LIVE. This is the authoritative gap view. Access date for all sources: `2026-11-27`.

**Legend:** ✅ confirmed by at least one source of the stated class; ⚠️ partial / indexed excerpt / single school instance; ❌ no evidence found; ⛔ not attempted in this milestone.

| # | Topic | LEGAL | OFFICIAL | DOCUMENTED PRACTICE | RESEARCH | INTERVIEW VALIDATED | ASSUMPTION only | Coverage | Key gap |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | School identity and configuration | ✅ LAW-03 s9/15/19/23 | ✅ OFF-01–03 | ⚠️ PRAC-08 (unofficial form) | ✅ OFF-02 | ⛔ | ⚠️ identifier syntax | Partial | U-06, U-07 |
| 2 | School types and institutional structures | ✅ LAW-03 s9 | ✅ OFF-01 | ⚠️ PRAC-05 | ✅ OFF-11 | ⛔ | ❌ | Partial | Official combined taxonomy |
| 3 | Multi-school architecture requirements | ⚠️ LAW-03 | ✅ OFF-01–03 | ⚠️ PRAC-02 | ✅ OFF-02 | ⛔ | ⚠️ tenancy | Partial | U-17 |
| 4 | School organisational structures | ✅ LAW-03 s36 | ✅ OFF-04/OFF-10 | ✅ PRAC-01–07 | ✅ RES-01 | ⛔ | ⚠️ reporting lines | Good | U-03, U-11 |
| 5 | ECD–A-Level structures | ⚠️ LAW-03 definition | ✅ OFF-05/OFF-06/OFF-07 | ⚠️ PRAC-05 | ✅ OFF-09 | ⛔ | ⚠️ crosswalk | Good | U-08, U-09 |
| 6 | Classes, streams, departments, campuses | ❌ | ⚠️ OFF-05 | ✅ PRAC-03–07 | ❌ | ⛔ | ⚠️ semantics | Partial | U-10, U-12 |
| 7 | Academic calendars, years, terms | ⚠️ OPS-02 definitions | ⚠️ signed calendar quoted via PRAC-09 | ✅ PRAC-09–12 | ❌ | ⛔ | ⚠️ day counts | Partial | C-07, official MoPSE calendar PDF |
| 8 | User types and stakeholder roles | ✅ LAW-03 s36, ss65–66 | ✅ OFF-04/OFF-10 | ✅ PRAC-03/04 | ✅ RES-02 | ⛔ | ⚠️ learner access | Good | U-11 |
| 9 | Authentication requirements | ⚠️ LAW-02 s18(4) general duty | ⚠️ REG-01/REG-03 guidance | ❌ | ✅ STD-03/04 (external) | ⛔ | ❌ | Partial | No Zimbabwean statutory MFA rule; U-04 |
| 10 | Role-based access control | ⚠️ LAW-02 s18(4) | ❌ | ❌ | ✅ STD-01/02 (external) | ⛔ | ❌ | Partial | No official local RBAC guidance |
| 11 | School-level permissions and delegation | ✅ LAW-03 ss65–66 | ✅ OFF-04 | ✅ PRAC-02 | ❌ | ⛔ | ⚠️ limits | Good | U-11 |
| 12 | Audit trails and accountability | ✅ LAW-02 ss13(g), 19–20, 24; LAW-03 s23 | ✅ OFF-04 | ⚠️ PRAC-06 (indexed) | ✅ STD-01 external | ⛔ | ❌ | Good | U-04 (log content/retention) |
| 13 | Configuration requirements | ✅ OPS-02 | ✅ OPS-06/OPS-07 | ❌ | ❌ | ⛔ | ⚠️ codes/formats | Partial | U-13 |
| 14 | Tenant/data isolation | ✅ OPS-01 | ❌ | ❌ | ❌ | ⛔ | ⚠️ topology | Partial | U-17 |
| 15 | Privacy and applicable legislation | ✅ LAW-01/02/03, REG-01 | ✅ REG-02, OBS-01/02 | ❌ | ✅ RES-L01/L02 | ⛔ | ❌ | Good | U-01, U-02, U-05 |
| 16 | Low bandwidth and infrastructure | ❌ | ✅ OPS-05/OPS-08 | ❌ | ✅ OPS-06 (2019), OPS-09 | ⛔ | ⚠️ coverage | Partial | School-level current measurements |
| 17 | Backup, recovery, resilience | ⚠️ OPS-01 general security | ✅ OPS-10 (safety guidance) | ❌ | ✅ OPS-06 (policy gap) | ⛔ | ⚠️ RPO/RTO | Partial | U-15 |
| 18 | School onboarding and configuration | ✅ OPS-02 | ⚠️ OPS-03/OPS-04 snippets, OPS-06/07 | ❌ | ❌ | ⛔ | ⚠️ exact extracts | Partial | U-16 |
| + | Stakeholder interviews | — | — | — | — | ⛔ **none conducted** | — | Not started | `P1-R19` |

## Evidence-class totals (topics with at least one source)

| Class | Topics covered | Count |
| --- | --- | --- |
| LEGAL | 1, 2, 3, 4, 8, 9, 11, 12, 13, 14, 15, 17, 18 | 13 |
| OFFICIAL | 1, 2, 3, 4, 5, 6, 7, 8, 11, 12, 13, 15, 16, 17, 18 | 15 |
| DOCUMENTED PRACTICE | 1, 2, 4, 5, 6, 7, 8, 11, 12 | 9 |
| RESEARCH | 1, 2, 3, 4, 5, 8, 9, 10, 12, 15, 16, 17 | 12 |
| INTERVIEW VALIDATED | — | **0** |
| ASSUMPTION only | Identifier syntax, tenancy topology, code formats, RPO/RTO | 4 clusters |

## Gap conclusion

No topic is fully closed: all 18 carry at least one unresolved question or an indexed-only source. The evidence base is sufficient to produce **PROPOSED** requirement candidates, but **not** sufficient to approve implementation requirements. Priority gaps for the next milestone are: official identifier definitions (U-06/U-07), current SDC regulations (U-03), legal applicability of CDPA licensing/breach rules (U-01/U-02), and stakeholder interviews (`P1-R19`).

## Rule

A topic may move to "closed" only when its key gap is resolved by a source entered in the [source register](source-register.md) or an INTERVIEW VALIDATED record, and the corresponding conflict/unresolved question is updated.
