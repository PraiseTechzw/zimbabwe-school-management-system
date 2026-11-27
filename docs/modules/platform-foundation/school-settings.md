# School Settings & Configuration Foundation

**Status:** implemented backend slice for issue #38; PR pending review.
**Design classification:** engineering mechanism confirmed by #38; concrete setting catalogue PROPOSED pending U-13.

This slice implements a school-scoped, typed and validated settings mechanism. It does **not** claim that the exposed settings are a verified catalogue of school-varying configuration. `U-13` remains unresolved and no stakeholder validation is claimed.

## Scope

Issue #38 requires settings that are school-scoped, typed/validated and auditable where critical, without universal calendar, code-format or structure defaults. Research does not identify which school settings genuinely vary, so this slice deliberately limits the catalogue to two framework-supported regional preferences:

| Setting | Type | Allowed values | Default |
| --- | --- | --- | --- |
| `locale` | string | `config('school-settings.locales')` (`en`) | `en` |
| `timezone` | string | any PHP/IANA timezone accepted by Laravel's `timezone` rule | `UTC` |

`locale`, `timezone` and defaults are configuration, not hard-coded domain facts. Adding currency, terminology, attendance, finance, module-enablement, calendar or structure settings requires U-13 resolution or a Product Owner decision and must not be added merely because the storage mechanism exists.

Explicitly out of scope: identity fields, identifiers, statuses, relationships, academic years/periods, educational levels, grades, classes, streams, offerings, roles/permissions (#37), the audit event model (#40), tenancy topology changes, fee settings, onboarding approvals and UI.

## Data model

### `school_settings`

One row per school, keyed by the server-resolved `school_id` with a database unique constraint.

| Column | Purpose |
| --- | --- |
| `school_id` | owning school; never client-supplied or mass-assignable |
| `locale`, `timezone` | typed setting values |
| `source` | required provenance for the current state |
| `effective_from` | date on which the current state takes effect |
| `version` | monotonically increasing current version, starting at 1 |
| `created_at`, `updated_at` | Eloquent timestamps |

A read never creates a row. `ResolveSchoolSettings` returns an unsaved model carrying configured defaults when the school has no row.

### `school_setting_revisions`

Append-oriented snapshots of the **previous** state. A revision is inserted in the same transaction as the update and is never modified by application code.

| Column | Purpose |
| --- | --- |
| `school_id`, `school_setting_id` | ownership and link to the current settings row |
| `version` | previous version number; unique per school |
| `values` | previous typed values as JSON |
| `source`, `effective_from` | previous provenance and effective date |
| `created_at`, `updated_at` | record timestamps |

This is minimal configuration provenance for #38 and PROPOSED NFR-013/WF-08. It is not the audit trail from #40: it has no actor, event type, retention policy or generic entity metadata. Those belong to the separate audit slice.

## Behaviour

- All HTTP routes require `auth` + `school.context`.
- `school_id` in a JSON body or query string is rejected with 422. An `X-School-Id` header can only select among schools resolved from the authenticated user's `school_user` memberships; an unauthorised selection is rejected by middleware with 403.
- Writes are validated twice: the FormRequest rejects client tenant input, and `ValidateSchoolSettings` revalidates at the domain boundary for internal callers.
- Unknown fields are not persisted; only allow-listed columns are fillable and the controller passes only `validated()` data to the action.
- Domain actions set `school_id` from the resolved `School`; no API payload can set it.
- The first save writes version 1 with no revision because there is no prior state. Each later save increments `version` and records the complete prior snapshot.
- Effective date is required to be `Y-m-d` when supplied and defaults to the current date. Source is required and cannot be blank.
- All writes run in a transaction. The school row and any existing settings row are locked with `lockForUpdate()` before the version is read and incremented, so concurrent writers serialise on one school. Database unique constraints are the final guard. SQLite serialises writers at the engine level, making the row lock a no-op there.
- Tenant-varying JSON responses send `Cache-Control: no-store` and `Vary: X-School-Id, school_id`.
- Revisions are ordered by version descending. Reads use `forCurrentSchool()`; unscoped settings queries fail closed when no school context exists.

## HTTP surface

See [School Settings API](../../api/school-settings.md).

## Assumptions and unresolved decisions

- **A-01:** regional locale/timezone preferences are a safe initial mechanism because they already exist as framework concepts; this is an engineering choice, not evidence that schools configure them differently.
- **A-02:** retaining source, effective date and prior version satisfies "auditable where critical" for this mechanism without implementing #40. Full audit events, actor attribution and retention remain deferred.
- **A-03:** defaults are minimal and config-driven. They do not assert a school calendar, school code/identifier format, academic structure, currency or terminology.
- **U-13:** which school-level settings genuinely vary remains unresolved. The catalogue must not expand until this is answered or a Product Owner makes an explicit decision.
- **#37:** role/permission enforcement is not implemented. Endpoints use authentication and school membership only.
- **#40:** audit event storage, actor metadata, tamper evidence and retention are not implemented.
- **D-04:** shared-database tenancy remains provisional pending a final ADR.

## Verification intent

Tests cover defaults without writes, validation, provenance and revision history, repeated-write versioning, tenant isolation, client `school_id` rejection, authorised header selection, unauthorised selection denial, database constraints and cascade behaviour. Run the repository's standard Pint, PHPStan/Larastan, Pest, Composer audit and Docker checks before review.
