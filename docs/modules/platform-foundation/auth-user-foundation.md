# Authentication and user foundation — implementation record

**Status:** IMPLEMENTED, pending review. **Issue:** `P1-I03` (#36). **Evidence class:** PROPOSED DESIGN / engineering baseline.

The execution gate is recorded on [`P1-G01` (#31)](https://github.com/PraiseTechzw/zimbabwe-school-management-system/issues/31). The PROPOSED specification remains unchanged; this record describes the implementation authorised by that recorded instruction, not a new Product Owner decision.

## Public interface

| Method | Path | Access | Purpose |
| --- | --- | --- | --- |
| `POST` | `/api/auth/login` | guest, throttled | Verify credentials and establish a web session |
| `POST` | `/api/auth/logout` | web session | End the guard session and rotate the CSRF token |
| `GET` | `/api/auth/me` | authenticated | Return the allow-listed current-user representation |

Routes are loaded into the `web` group by `AuthServiceProvider` from `routes/auth.php`. This keeps `routes/web.php`, `bootstrap/app.php` and `AppServiceProvider.php` untouched while the school PRs are open; route membership is asserted by a test. Any move to the canonical routing configuration should preserve those middleware assertions.

## Data ownership and invariants

- This module owns the `users` lifecycle columns: `status`, `failed_login_attempts`, `locked_until` and `last_login_at`. It does not own identity, school membership or role assignment.
- Only `active` accounts authenticate. Five consecutive failures lock the account for 15 minutes; success clears the counter and lock.
- Login is throttled at 10 requests per minute per IP + normalised email with an independent 30/minute per-IP backstop. Thresholds are provisional code constants, not confirmed resilience targets.
- User creation can attach server-validated `school_ids`; no auth endpoint accepts a school identifier as proof of access. School-owned reads remain guarded by `ResolveSchoolContext`, which only selects among the authenticated user's `school_user` memberships.

## Dependencies

`App\Models\User`, Laravel's session guard, hash driver, cache-based rate limiter and web CSRF middleware. No new package, no separate deployable and no frontend dependency.

## Evolution plan

After a school identity/isolation slice reaches `main`, add server-side membership verification and cross-school regression tests to the login/current-user flow. Roles and permissions remain a separate module.

## Scope delivered

- Additive `users` migration for account lifecycle: `status` (fail-closed `disabled` default), `failed_login_attempts`, `locked_until`, `last_login_at`. `status` is not mass-assignable.
- `AccountStatus` enum (`active`, `suspended`, `disabled`); only `active` accounts may authenticate.
- `CreateUser` domain action: server-side validation, unique/normalised email, hashed password, optional server-validated school memberships, no public registration route.
- `AttemptLogin` domain action: exactly one hash verification per attempt, per-account lockout (5 failures → 15 minutes) protected by a row lock, and failure counter reset on success.
- `LogoutUser` action and JSON endpoints for login, logout and current user.
- `AuthenticatedUserResource`: explicit allow-list of response fields.
- Factory states for suspended, disabled and locked accounts; Pest feature coverage.

## Security controls

| Control | Implementation |
| --- | --- |
| Password hashing | Laravel `hashed` cast and `Hash` facade; no plaintext storage or logging. |
| Password baseline | `Password::min(12)->max(64)->letters()->mixedCase()->numbers()->symbols()` with confirmation. The 64-character cap stays below bcrypt's 72-byte truncation boundary for typical input. Provisional engineering baseline, not a Product Owner-confirmed rule. `uncompromised()` is intentionally not used because it adds a network dependency. |
| Abuse protection | Stacked `throttle:login` and `throttle:login-ip` limiters: 10/minute per IP + normalised email and an independent 30/minute per-IP backstop, plus account lockout after 5 failures. |
| Lockout integrity | Failures are read and written under a row lock so concurrent guesses cannot lose increments. |
| User enumeration | Unknown email, wrong password, suspended, disabled and locked accounts all return the same 422 message. |
| Timing equalisation | Exactly one hash verification runs on every path, including unknown and inactive accounts. This is not a constant-time guarantee. |
| Session security | Session ID regenerated on login; session invalidated and CSRF token regenerated on logout. Logout requires authentication. |
| CSRF | All auth routes are in the `web` middleware group. |
| Sensitive data | Response allow-list plus `Hidden` attributes exclude hashes and remember tokens. Factory passwords are random unless a fixture sets one explicitly. |
| Validation | Form Request on login; validator inside the creation action. |
| Lifecycle integrity | `status` is outside mass assignment and is set explicitly by `CreateUser`; the migration default is `disabled` for pre-existing rows. |
| School membership | `CreateUser` validates optional `school_ids` against existing schools before attaching them. Login ignores any client-supplied school ID; `ResolveSchoolContext` denies unowned or conflicting school selections on school-owned endpoints. |
| Cookie hardening | `http_only=true` and `same_site=lax` defaults retained; secure cookies fail closed outside `local` unless explicitly overridden. |

## Deliberately not implemented

- Roles, permissions and authorisation decisions (`P1-I04`).
- Any new school-owned resource, school-selection UI or tenancy-topology decision; school context remains owned by the School domain.
- Admissions, learners, attendance, finance, exams, HR, boarding, UI or dashboards.
- Public self-registration and password-reset flows.

## Dependency on the school slice

PR #42 was verified CLOSED and unmerged before implementation began, so nothing was built on that branch and its code was never assumed to exist on `main`. During implementation, PRs #41 (school identity) and #43 (server-side isolation) merged into `main`; this branch was rebased onto that state rather than kept on a stale base.

School membership integration is therefore included: `CreateUser` validates and attaches `school_ids` server-side, while cross-school regression tests exercise a real login session against `ResolveSchoolContext`. No auth endpoint accepts a school identifier as proof of access, and no client-supplied value can widen the authorised school context. The tenancy topology remains the provisional shared-database boundary documented in the School module.

## Residual risks

- Lockout can be abused to deny service to a known account, including from rotating source addresses; the per-IP backstop limits spraying but does not eliminate this. An account-level unlock path and progressive backoff are follow-up work.
- The migration defaults existing rows to `disabled`, so populated databases need a deliberate activation/backfill step after an audit. There are no production users at this stage.
- Response uniformity intentionally withholds lockout reasons from end users; support tooling is out of scope.
- Concurrent lockout behaviour is protected by a row lock; no parallel-process regression test was added because the test suite runs serially.
- Throttling relies on the configured cache store. Production must use a shared cache, not the local array driver; `.env.example` defaults to the database store.
- IP-based throttling needs trusted-proxy configuration when TLS terminates at a reverse proxy, otherwise all clients may share the proxy address. Deployment-level proxy trust is an operator responsibility and is not configured by this slice.
