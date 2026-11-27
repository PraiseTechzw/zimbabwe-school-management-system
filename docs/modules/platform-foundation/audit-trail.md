# Audit trail foundation — implementation record

**Status:** IMPLEMENTED, pending review. **Issue:** `P1-I07` (#40). **Evidence class:** PROPOSED DESIGN / engineering baseline.

This slice is the Phase 1 audit foundation. It records deliberate administrative and system events; it is not a generic request logger and does not infer permissions or compliance claims.

## Scope

Included:

- Append-only `audit_events` records.
- Minimal actor identity, server-resolved school context, action/category classification, resource identity, event and persistence timestamps, correlation identifiers, allow-listed sources, structured metadata and safe before/after summaries.
- Explicit write action and read-only query action.
- Redaction of sensitive metadata keys and rejection of unsupported object values.
- Deliberate integration with academic calendar configuration writes as the pattern for later modules.

Not included:

- Admissions, learner, finance, welfare, staff, governance or other school workflows.
- Authentication changes, roles, permissions or HTTP audit endpoints.
- Automatic capture of all requests or all model fields.
- Retention schedules, legal hold, archival, external SIEM export or compliance certification.
- A user interface.

## Data model

`audit_events` is owned by the audit domain.

| Column | Purpose |
| --- | --- |
| `school_id` | Server-resolved school for tenant events; `NULL` only for explicit system scope. Foreign key uses `RESTRICT` on deletion. |
| `actor_type`, `actor_id` | Minimal actor identity (`user`, `system`, `job`) and identifier only. No names, email addresses or personal attributes. |
| `action`, `category` | Lowercase action slug and optional classification slug. |
| `resource_type`, `resource_id` | The entity that changed, represented by type and identifier. |
| `occurred_at` | Event time supplied by the domain caller or defaulted to now. |
| `created_at` | Server persistence time. There is no `updated_at` column. |
| `correlation_id` | Allow-listed request/job correlation value, or `NULL`. |
| `source` | Allow-listed origin: `domain`, `http`, `cli`, `job` or `import`. |
| `metadata` | JSON object reduced by the safe metadata sanitizer. |
| `changes` | JSON object containing only `before` and `after` summaries. |

Indexes support school/time reads, school/action reads, school/resource reads and correlation lookups.

## Write path

Domain modules call `RecordAuditEvent` with an `AuditScope` and an `AuditEventData` value object. `AuditScope::forSchool()` accepts an already authorised `School` model; there is no factory accepting a raw client-supplied ID. `AuditScope::system()` is the only way to write a null-school event.

The actor is resolved through the `ActorResolver` contract. The default implementation reads the existing auth guard and falls back to an explicit `system` actor. Background work can pass `AuditActor::job(...)` explicitly. Future authentication or job-context changes can replace the resolver without altering domain modules.

Correlation starts from an explicit value when the caller supplies one; otherwise the allow-listed `X-Correlation-Id` or `X-Request-Id` header is accepted only when it matches a safe, length-limited format.

The recorder is the only supported writer. It:

1. Validates the actor type, source and correlation value against `config/audit.php`.
2. Sanitizes metadata and changes recursively.
3. Persists an append-only `AuditEvent` inside the caller's existing transaction when the domain action records there.

If audit input is invalid, the recorder throws before writing. Domain actions in this slice call it inside their database transaction, so an audit failure rolls back the associated domain write.

## Safe metadata and redaction

`SanitizeAuditData` accepts only small scalar values, lists, backed enums and dates. It rejects objects, unsupported types, overly large structures and invalid keys. Sensitive key names are redacted recursively to `[REDACTED]` using patterns in `config/audit.php`, including passwords, tokens, credentials, card/bank details and unnecessary medical, safeguarding or document fields. Whole request bodies are never accepted.

Changing the redaction patterns changes the safety floor; operators must review them before adding new metadata keys to a domain module.

## Append-only protection

- `AuditEvent` has no `updated_at` column.
- Model `updating` and `deleting` events throw `LogicException`.
- The model's Eloquent builder overrides `update`, `increment`, `decrement`, `incrementEach`, `decrementEach` and `delete` to throw.
- The school foreign key uses `RESTRICT`, so a school with audit history cannot be deleted accidentally.
- No HTTP write or delete route exists.

These are application and schema protections. A database superuser or raw `DB::table('audit_events')` query remains outside this boundary and must be controlled by deployment ownership and backup policy.

## Reading audit events

`QueryAuditEvents` is read-only and requires a `School` model. It filters by action, category, resource, actor and date range, then returns deterministic descending pagination. It is intended for trusted server-side callers such as a future permission-protected administration screen.

No HTTP endpoint is exposed in this slice because roles and permissions are not implemented. Exposing one before that slice would create an authorisation gap.

## Domain integration example

The academic calendar actions now emit deliberate events for year and period creation/update. This demonstrates the intended pattern without changing calendar business rules:

```php
app(RecordAuditEvent::class)->execute(
    AuditScope::forSchool($school),
    new AuditEventData(
        action: 'academic_year.updated',
        resourceType: 'academic_year',
        resourceId: (string) $year->id,
        category: 'school_configuration',
        metadata: ['source' => $year->source],
        changes: [
            'before' => ['status' => $previousStatus],
            'after' => ['status' => $year->status],
        ],
        source: 'domain',
    ),
);
```

Later modules should copy this shape: select named fields deliberately, use explicit before/after summaries and pass the server-resolved school. They must not pass whole requests, model attribute arrays or client identifiers.

## Tenant isolation and privacy

- School context is never taken from request input by the audit domain.
- Query results are always constrained to the supplied authorised school.
- Cross-school calendar writes remain rejected before an audit record is created.
- Actor records contain identifiers only.
- Sensitive metadata keys are redacted before persistence.
- Audit responses are not exposed over HTTP in this slice.

## Known limitations and owner decisions

- There is no retention, archival or legal-hold policy; the evidence does not supply numeric retention requirements.
- System-level retrieval is intentionally not exposed until its authorisation model is defined.
- Raw SQL and privileged database roles can still bypass Eloquent immutability; this needs deployment-level control.
- A future import or job path must pass an explicit system/job actor and its own server-resolved scope.
- Roles/permissions are still required before any audit UI or API surface is opened.
- The school foreign key `RESTRICT` may require an owner decision if a formal school-deletion workflow is introduced later.
