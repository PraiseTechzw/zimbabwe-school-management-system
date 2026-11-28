# Learner Management API (PROPOSED contract)

**Status:** implemented and locally verified on `feature/backend-learner-management-foundation` against merged `origin/main` `40d6c0f`; see [verification evidence](../reports/learner-management-verification.md). It remains **PROPOSED** until human review and merge, is not a deployed API, and has no frontend UI in this slice.
**Module record:** [Learner Management foundation](../modules/learner-management.md).
**Evidence:** [Findings 07 — learner records](../research/findings/07-learner-records.md). No field below is represented as a statutory or MoPSE-required field.

The routes will be mounted by `LearnerManagementServiceProvider` under `/api` with `web`, `auth` and `school.context`. Reads require `learners.view`; writes require `learners.manage`. Both permissions are currently granted only through the `school_admin` wildcard role.

## Tenant and response conventions

- A user with one school needs no selection input; a user with multiple schools must send `X-School-Id`.
- `school_id` in a request body or query string is rejected with `422` and is never accepted as proof of access.
- An unauthorised `X-School-Id` returns `403`; an ambiguous selection returns `400`; an unauthenticated request returns `401`.
- All JSON responses send `Cache-Control: no-store` and `Vary: X-School-Id, school_id`.
- Route IDs are numeric. A missing or foreign learner returns the same generic `404` response.
- Cross-school `class_offering_id` values produce a generic `422`; no other school's data is echoed.
- Validation failures use Laravel's standard JSON shape:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "school_id": ["The school id field is prohibited."],
    "full_name": ["The full name field is required."]
  }
}
```

## Learner object

```json
{
  "id": 12,
  "school_id": 7,
  "learner_reference": "LRN-01JZ8KQ3P4M5N6P7Q8R9S0T1V",
  "full_name": "Example Learner",
  "date_of_birth": null,
  "status": "registered",
  "class_offering_id": null
}
```

| Field | Type | Notes |
| --- | --- | --- |
| `id` | integer | Internal database ID. |
| `school_id` | integer | Server-resolved owning school. |
| `learner_reference` | string | Internal `LRN-<uppercase ULID>` reference; not national, statutory or exam-compatible. |
| `full_name` | string | Trimmed learner name; no name-part convention is assumed. |
| `date_of_birth` | string or null | `Y-m-d`; optional. |
| `status` | string | `registered` only in this slice; record status, not enrolment status. |
| `class_offering_id` | integer or null | Optional indicative placement link to an owned Academic Structure offering. |

Timestamps are stored but not returned. No guardian, medical, address, contact, identifier or assessment field is exposed.

## Routes

| Method | Path | Permission | Response |
| --- | --- | --- | --- |
| `GET` | `/api/learners` | `learners.view` | `{"school_id":7,"search":null,"learners":{paginator}}`, `200` |
| `POST` | `/api/learners` | `learners.manage` | `{"learner":{...}}`, `201` |
| `GET` | `/api/learners/{learner}` | `learners.view` | `{"learner":{...}}`, `200` |
| `PUT\|PATCH` | `/api/learners/{learner}` | `learners.manage` | `{"learner":{...}}`, `200` |

There is no delete, archive, restore, status-transition or export route.

## List query

| Parameter | Rules | Default |
| --- | --- | --- |
| `school_id` | prohibited | n/a |
| `search` | optional string, max 120; trimmed; empty means no filter | `null` in the echo |
| `per_page` | optional integer, `1..learner-management.max_per_page` (currently `100`) | `25` |
| `page` | optional integer, minimum `1` | Laravel default `1` |

`search` matches `full_name` or `learner_reference` case-insensitively. SQL wildcard characters in the term are escaped. Results are ordered by `id` descending.

The `learners` envelope is a Laravel paginator array:

```json
{
  "school_id": 7,
  "search": "example",
  "learners": {
    "current_page": 1,
    "data": [{ "id": 12, "school_id": 7, "learner_reference": "LRN-...", "full_name": "Example Learner", "date_of_birth": null, "status": "registered", "class_offering_id": null }],
    "first_page_url": "...",
    "from": 1,
    "last_page": 1,
    "last_page_url": "...",
    "links": [{ "url": null, "label": "&laquo; Previous", "active": false }],
    "next_page_url": null,
    "path": "...",
    "per_page": 25,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

## Create

`POST /api/learners`

Accepted fields:

| Field | Rule |
| --- | --- |
| `full_name` | required string, max 255, not blank after trimming |
| `date_of_birth` | optional nullable `Y-m-d`, not in the future |
| `class_offering_id` | optional nullable integer, minimum 1; must be an active offering owned by the current school when `learner-management.require_active_placement` is true |

Prohibited fields: `school_id`, `learner_reference`, `status`, `id`, `created_at` and `updated_at`. Unlisted fields are ignored rather than validated or stored; clients must not send them or rely on them having an effect.

Example request:

```json
{
  "full_name": "  Example Learner  ",
  "date_of_birth": "2012-05-04",
  "class_offering_id": null
}
```

The stored `full_name` is `Example Learner`. The server sets `school_id`, `learner_reference` and `status`; the caller cannot influence them. A successful create returns `201` with the learner envelope.

## Retrieve

`GET /api/learners/{learner}`

The route parameter is scoped to the current school before lookup. A foreign ID and a nonexistent ID both return `404`.

## Update

`PUT|PATCH /api/learners/{learner}`

Accepted fields:

| Field | Rule |
| --- | --- |
| `full_name` | sometimes required string, max 255, not blank after trimming |
| `date_of_birth` | sometimes nullable `Y-m-d`, not in the future; `null` clears it |
| `class_offering_id` | sometimes nullable integer, minimum 1; `null` clears the placement; non-null values must be an active offering owned by the current school when the config flag is true |

Omitted fields are unchanged. `school_id`, `learner_reference`, `status`, `id` and timestamps are prohibited; unlisted fields are ignored. No update can change ownership, reference or record status. A placement must be cleared with an explicit `null` before its offering can be deleted. A successful update returns `200` with the learner envelope.

## Audit behaviour

Write actions append `learner.registered` or `learner.updated` in category `learner_record`, scoped to the server-resolved school, inside the database transaction. Snapshots contain only `status`, `full_name_present`, `date_of_birth_present` and `class_offering_id`. Names, dates of birth and learner references are not logged. Reads are not audited. Rejected writes produce no event. There is no HTTP read endpoint for audit events in this slice.

## Frontend handoff constraints

- Never send `school_id`; tenancy comes from the authenticated session and school selection.
- Treat `status` and `learner_reference` as read-only.
- Do not infer staff permission from role names or hide actions based on a local permission map; the backend permission gate is authoritative and LM-U-07 is open.
- Do not build admissions, enrolment, guardian, document or export flows from this contract. They are out of scope and unverified.
- Until this branch is merged and its verification evidence is recorded, any UI using these shapes must be labelled as built against a PROPOSED contract, not a verified backend endpoint.
