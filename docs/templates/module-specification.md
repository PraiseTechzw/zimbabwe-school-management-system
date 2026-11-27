# Module specification: <bounded context>

> A module is a domain boundary inside the Laravel monolith, not a separate deployable service. See ADR-0001.

- Status: Draft | Accepted
- Owner: <name>
- Related feature issues: <links>

## Responsibility

One paragraph describing what this context owns and what it explicitly does not own.

## Public interface

- Actions/commands exposed to other contexts:
- Query/read models exposed:
- Events emitted/consumed:

## Data ownership

Tables owned by this context, key constraints, indexes, retention and privacy classification. Other contexts must not write these tables directly.

## Dependencies

Other contexts used, external services, and configuration keys. No circular dependencies.

## Invariants and authorisation

Business rules that must always hold and the permission checks that protect them.

## Testing strategy

Unit tests for invariants, feature tests for public interface, migration/backfill verification.

## Evolution plan

How the context will be introduced, changed and retired without breaking callers.
