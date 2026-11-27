# Frontend API dependency tracker

Updated: 2026-11-27. Only endpoints verified by reading the repository are listed. Provisional contracts are labelled and must not be treated as backend promises.

## Verified endpoints

| Endpoint | Method | Auth / tenancy | Response shape (verified in controller) | UI consumer | Integration status |
| --- | --- | --- | --- | --- | --- |
| `/api/auth/login` | POST | `throttle:login`, `throttle:login-ip`; body `{email, password}` | `{user: {id, name, email, status, email_verified_at, last_login_at}}` | Sign-in form | Available — integration pending |
| `/api/auth/logout` | POST | `auth`; CSRF (web middleware) | `{message: "Logged out."}` | User menu | Available — integration pending |
| `/api/auth/me` | GET | `auth` | `{user: {id, name, email, status, email_verified_at, last_login_at}}` | Shell user row, dashboard | Available — integration pending |
| `/api/school` | GET | `auth`, `school.context` | `{school: {id, name, type}}`; `Cache-Control: no-store`, `Vary: X-School-Id` | School context strip | Available — integration pending |
| `/api/school/identifiers` | GET | `auth`, `school.context` | `{school_id, identifiers: [{id, school_id, type, value, verification_status}]}` | School identity view | Available — integration pending |
| `/api/academic-years/current` | GET | `auth`, `school.context` (group middleware from `routes/web.php`) | `{school_id, academic_year: null \| {id, school_id, name, start_date, end_date, status, source, source_reference, source_published_at}}` | Dashboard current-period panel | Available — integration pending |
| `/api/academic-years` | GET | same | `{school_id, academic_years: [year payload]}` | Academic calendar list | Available — integration pending |
| `/api/academic-years/{id}/periods` | GET | same | `{school_id, academic_year_id, periods: [period payload]}` | Academic calendar detail | Available — integration pending |
| `/api/academic-calendar/validation` | GET | same; requires `academic_year_id` | `{school_id, academic_year_id, valid, issues}` | Calendar validation panel | Available — integration pending |

## Rules for integration

- Never send a client-supplied `school_id`: the backend rejects it (`prohibited`) on calendar endpoints and resolves tenancy server-side.
- Use the session cookie and CSRF token from the Laravel page; do not store credentials in browser storage.
- Treat every payload field as untrusted input: escape on output in Blade, never inject raw HTML.
- A missing endpoint means a clearly labelled mock adapter, not an invented URL. Add provisional contracts here with `PROVISIONAL` in the status before writing UI against them.
- Keep fetch/request counts low: one request per block, no polling on the dashboard unless a real need is documented.

## Known gaps

- No endpoint for school switching, notifications, dashboards aggregates, or a module availability catalogue. Navigation uses local configuration and honest "not available yet" states.
- No frontend permission map yet; actions are hidden only where the backend contract explicitly supports it. Frontend visibility is never treated as authorisation.
