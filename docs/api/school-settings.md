# School Settings API

All endpoints are mounted by `SchoolSettingsServiceProvider` under `/api` with `web`, `auth` and `school.context` middleware. They return JSON and require an authenticated user with at least one school membership.

Tenant selection:

- A user with one school needs no selection input.
- A user with multiple schools must send `X-School-Id`.
- `school_id` in the query string or request body is rejected with `422` and is never accepted as proof of access.
- An `X-School-Id` that is not one of the user's memberships returns `403`.
- Responses include `Cache-Control: no-store` and `Vary: X-School-Id, school_id`.

## `GET /api/school-settings`

Returns the resolved settings for the current school. If no row exists, the configured defaults are returned without creating data.

```json
{
  "school_id": 1,
  "settings": {
    "locale": "en",
    "timezone": "UTC"
  },
  "revision": {
    "version": 1,
    "source": null,
    "effective_from": null
  }
}
```

## `PUT|PATCH /api/school-settings`

Creates or updates the current school's settings. Only allow-listed fields are accepted; `school_id` is prohibited.

| Field | Rules |
| --- | --- |
| `source` | required string, max 255, not blank |
| `locale` | optional; must be in `school-settings.locales` |
| `timezone` | optional; valid PHP/IANA timezone |
| `effective_from` | optional nullable `Y-m-d`; defaults to today |

Example request:

```json
{
  "locale": "en",
  "timezone": "Africa/Harare",
  "source": "School configuration register",
  "effective_from": "2031-03-01"
}
```

The response has the same shape as `GET`. `version` is 1 on first save and increments on every later save. The first save creates no revision; each later save creates a revision containing the previous state.

Validation errors use Laravel's standard JSON shape, for example:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "school_id": ["The school id field is prohibited."],
    "timezone": ["The timezone field must be a valid timezone."]
  }
}
```

## `GET /api/school-settings/revisions`

Returns previous versions for the current school, newest first.

```json
{
  "school_id": 1,
  "revisions": [
    {
      "id": 2,
      "version": 1,
      "values": {
        "locale": "en",
        "timezone": "UTC"
      },
      "source": "Initial register entry",
      "effective_from": "2031-01-01",
      "created_at": "2031-02-01T09:00:00.000000Z"
    }
  ]
}
```

Revisions are provenance snapshots, not the audit event model from issue #40.
