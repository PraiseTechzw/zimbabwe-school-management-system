# Frontend progress tracker

Updated: 2026-11-27. Status vocabulary: `NOT STARTED`, `UI IN PROGRESS`, `UI COMPLETE`, `MOCK INTEGRATION`, `API INTEGRATION IN PROGRESS`, `API INTEGRATED`, `TESTED`, `BLOCKED`.

Gate: ADR-0003 accepted 2026-11-27 (Blade + Livewire + Alpine + Tailwind). Stage A is implemented and tested on branch `feat/frontend-foundation`. School modules still require their own approved specification; the navigation catalogue lists planned destinations but only three screens have working implementations.

## Stage A — foundation

| Deliverable | Status | Screens / components | API dependencies | Mock data | Tests | Known issues / next task |
| --- | --- | --- | --- | --- | --- | --- |
| ADR-0003 acceptance + design source of truth | TESTED | `ADR-0003`, `PRODUCT.md`, `DESIGN.md` | None | None | N/A | Prototype evidence recorded below; no new ADR required. |
| Design tokens + reusable component set | TESTED | Tokens in `resources/css/app.css`; buttons, inputs, cards, tables, badges, states, nav and icons in `resources/views/components` | None | None | Frontend suite + visual inspector | No remote fonts or trackers. |
| Application shell (sidebar, mobile sheet, header, breadcrumbs, title row) | TESTED | `components/app-shell.blade.php`, `components/nav-list.blade.php` | `GET /api/auth/me`, `GET /api/school` | None | Frontend suite covers session/CSRF contract and navigation visibility | School switcher is deliberately not built: no verified endpoint exists. |
| Sign-in screen | TESTED | `frontend/login.blade.php` | `POST /api/auth/login` | None | Frontend suite + real-browser login/logout smoke | Authenticated visitors are redirected by the existing `guest` middleware. |
| Dashboard framework | TESTED | `Livewire/Dashboard.php`, `livewire/dashboard.blade.php` | `GET /api/auth/me`, `GET /api/school`, `GET /api/academic-years/current`, `GET /api/school/identifiers` | Opt-in `FRONTEND_DEMO=false` aggregate figures, labelled `Demo data` | Frontend suite renders real seeded school/year/identifier data | Cross-module counts stay empty until endpoints exist. |
| School profile + academic calendar read views | TESTED | `frontend/school-profile.blade.php`, `frontend/academic-calendar.blade.php` | `GET /api/school/identifiers`, `GET /api/academic-years`, `GET /api/academic-years/{id}/periods` | None | Frontend suite asserts tenant scoping | Read-only; no mutation screens. |
| API client + data adapter | TESTED | `resources/js/api.js`, `app/Frontend/*` | Existing endpoints in `api-dependencies.md` only | Isolated opt-in demo decorator | Frontend suite + browser network observation | Server adapter reads the same queries in process; no invented HTTP contracts. |
| Frontend tests + local docs | TESTED | `tests/Feature/Frontend/FrontendFoundationTest.php`, this tracker, `api-dependencies.md`, `docs/development/setup.md` | None | None | 10 frontend tests, 37 assertions | Commands and exit codes recorded below. |

## Stage B–O — school modules

All modules are `NOT STARTED` unless listed below. A navigation entry is not a claim that a module is available; planned entries lead to an explicit "not built yet" notice.

| Module | Status | Screens | API dependencies | Integration | Tests | Next task |
| --- | --- | --- | --- | --- | --- | --- |
| School identity / profile | TESTED (read-only) | School profile | `GET /api/school`, `GET /api/school/identifiers` | Server-side read adapter | Frontend suite | Stage B may add mutation flows after specification approval. |
| Academic calendar | TESTED (read-only) | Academic calendar list | `GET /api/academic-years`, `GET /api/academic-years/current`, `GET /api/academic-years/{id}/periods` | Server-side read adapter | Frontend suite | Stage B may add validation/management flows after specification approval. |
| Admissions, learners, guardians, enrolment, academics, attendance, finance, staff, welfare, boarding, transport, operations, library, activities, communication, governance, parent/learner portals | NOT STARTED | — | Not verified | None | — | Requires approved module specification and endpoint contract. |

## Prototype evidence (ADR-0003 condition)

Recorded 2026-11-27 against a local `php artisan serve` instance bound to a temporary SQLite database. Demo mode was enabled only for this local visual check; the committed default is `FRONTEND_DEMO=false`.

**Built assets** (`npm run build`, exit 0):

- 4 modules transformed; `public/build/manifest.json` 0.33 kB / gzip 0.16 kB.
- CSS: 46.78 kB / gzip 11.26 kB.
- Application JS: 1.56 kB / gzip 0.85 kB.
- Two consecutive builds produced the same `app-BZI3rIXv.css` hash; compiled Blade views under `storage/` are deliberately excluded from Tailwind source scanning so builds stay reproducible.

**Observed page loads** (Chromium, 1440×900, built assets, no cache):

- Form-heavy sign-in: 3 requests — HTML document, CSS (47,081 B transfer), JS (1,862 B transfer). No remote font, image, tracker or XHR.
- Read-heavy dashboard: 4 requests — HTML document, the same CSS and JS, plus the cached Livewire runtime (167,441 B transfer). No initial XHR or Livewire round trip was observed.
- Interaction: filled credentials, `POST /api/auth/login`, redirected to `/app`, dashboard rendered real seeded school/year/identifier values, `POST /api/auth/logout` returned to `/login`. Browser console showed zero errors.
- Visual checks: design-source lint returned 0 FAIL / 0 WARN. The rendered dashboard inspector returned 0 findings at desktop, tablet and mobile widths with the documented system-font exception. The sign-in inspector returned one accepted `hero-void` warning at desktop; the form remains compact and no third-party assets are loaded.

Conclusion: the measured client payload is small and the server-rendered pages need no extra round trip on first load. The evidence supports the accepted stack; Inertia is not required for Stage A.

## Verification log

All checks below were run locally on 2026-11-27. No remote GitHub Actions result is claimed. The first three entries are the pre-frontend baseline; the `make verify` baseline ran against the original checkout and is not evidence for this worktree. The worktree was verified with the equivalent isolated Compose command recorded below.

| Date | Command | Exit code | Evidence |
| --- | --- | --- | --- |
| 2026-11-27 | `make verify` | 0 | 96 tests, 352 assertions passed (pre-frontend baseline, original checkout) |
| 2026-11-27 | `docker compose config -q` | 0 | No output (baseline) |
| 2026-11-27 | `npm run build` | 0 | 3 modules; CSS 55.21 kB / gzip 11.73 kB; JS 0.00 kB / gzip 0.02 kB (baseline) |
| 2026-11-27 | `npm ci` | 0 | Dependencies installed from `package-lock.json` |
| 2026-11-27 | `php artisan test` | 0 | 129 passed, 440 assertions |
| 2026-11-27 | `./vendor/bin/pint --test` | 0 | `{"tool":"pint","result":"passed"}` |
| 2026-11-27 | `./vendor/bin/phpstan analyse --no-progress --memory-limit=1G` | 0 | `{"tool":"phpstan","result":"passed","errors":0}` |
| 2026-11-27 | `composer audit --locked` | 0 | No security vulnerability advisories found |
| 2026-11-27 | `npm audit --audit-level=high` | 0 | found 0 vulnerabilities |
| 2026-11-27 | `npm run build` | 0 | 4 modules; CSS 46.78 kB / gzip 11.26 kB; JS 1.56 kB / gzip 0.85 kB; stable hash across two builds |
| 2026-11-27 | `docker compose config -q` | 0 | No output |
| 2026-11-27 | `make COMPOSE="docker compose -p zimschool-fe-verify" verify` | 0 | Isolated Compose project with this worktree bind-mounted: Pint, PHPStan, Composer audit and 129 tests / 440 assertions passed; original dev stack untouched |
| 2026-11-27 | `node .../lint.mjs resources/views resources/css resources/js --app` | 0 | 0 FAIL, 0 WARN |
| 2026-11-27 | `node .../inspect.mjs <dashboard snapshot> --app --allow font-not-dealt` | 0 | 0 findings at 1440, 768 and 390 px |
| 2026-11-27 | `node .../inspect.mjs http://127.0.0.1:8010/login --app --allow font-not-dealt` | 0 | One accepted `hero-void` warning at 1440 px |
