# Frontend progress tracker

Updated: 2026-11-27. Status vocabulary: `NOT STARTED`, `UI IN PROGRESS`, `UI COMPLETE`, `MOCK INTEGRATION`, `API INTEGRATION IN PROGRESS`, `API INTEGRATED`, `TESTED`, `BLOCKED`.

Gate: ADR-0003 accepted 2026-11-27 (Blade + Livewire + Alpine + Tailwind). Stage A is merged into `main` as PR #48. Stage B calendar administration was implemented on `feat/frontend-stage-b-calendar-admin` and merged into `main` as PR #56 by the repository owner on 2026-11-27 after local verification and passing GitHub checks. The merged Academic Structure backend (PR #59) is now recorded in `api-dependencies.md` as a verified API-ready contract; no frontend UI has been built for it. School modules still require their own approved specification; the navigation catalogue lists planned destinations but only sign-in, dashboard, school profile, calendar read view and calendar management have working implementations.

## Stage A — foundation

| Deliverable | Status | Screens / components | API dependencies | Mock data | Tests | Known issues / next task |
| --- | --- | --- | --- | --- | --- | --- |
| ADR-0003 acceptance + design source of truth | TESTED | `ADR-0003`, `PRODUCT.md`, `DESIGN.md` | None | None | N/A | Prototype evidence recorded below; no new ADR required. |
| Design tokens + reusable component set | TESTED | Tokens in `resources/css/app.css`; buttons, inputs, cards, tables, badges, states, nav and icons in `resources/views/components` | None | None | Frontend suite + visual inspector | No remote fonts or trackers. |
| Application shell (sidebar, mobile sheet, header, breadcrumbs, title row) | TESTED | `components/app-shell.blade.php`, `components/nav-list.blade.php` | `GET /api/auth/me`, `GET /api/school` | None | Frontend suite covers session/CSRF contract and navigation visibility | School switcher is deliberately not built: no verified endpoint exists. |
| Sign-in screen | TESTED | `frontend/login.blade.php` | `POST /api/auth/login` | None | Frontend suite + real-browser login/logout smoke | Authenticated visitors are redirected by the existing `guest` middleware. |
| Dashboard framework | TESTED | `Livewire/Dashboard.php`, `livewire/dashboard.blade.php` | `GET /api/auth/me`, `GET /api/school`, `GET /api/academic-years/current`, `GET /api/school/identifiers` | Opt-in `FRONTEND_DEMO=false` aggregate figures, labelled `Demo data` | Frontend suite renders real seeded school/year/identifier data | Cross-module counts stay empty until endpoints exist. |
| School profile + academic calendar read views | TESTED | `frontend/school-profile.blade.php`, `frontend/academic-calendar.blade.php` | `GET /api/school/identifiers`, `GET /api/academic-years`, `GET /api/academic-years/{id}/periods` | None | Frontend suite asserts tenant scoping | Read-only; management added in Stage B. |
| API client + data adapter | TESTED | `resources/js/api.js`, `app/Frontend/*` | Existing endpoints in `api-dependencies.md` only | Isolated opt-in demo decorator | Frontend suite + browser network observation | Server adapter reads the same queries in process; no invented HTTP contracts. |
| Frontend tests + local docs | TESTED | `tests/Feature/Frontend/FrontendFoundationTest.php`, this tracker, `api-dependencies.md`, `docs/development/setup.md` | None | None | 10 frontend tests, 37 assertions | Commands and exit codes recorded below. |

## Stage B — academic calendar administration

Implemented against endpoints and domain actions already present in this branch. There is no mock data: displayed years, periods and validation issues come from the authorised school's records. The management route is `/app/academic-calendar/manage`, linked from the existing read-only calendar.

| Deliverable | Status | Screens / components | API dependencies | Integration | Tests | Known issues / next task |
| --- | --- | --- | --- | --- | --- | --- |
| Academic year create/edit | TESTED | `Livewire/CalendarAdministration.php`, `livewire/calendar-administration.blade.php` | `POST /api/academic-years`, `PUT\|PATCH /api/academic-years/{academicYear}` | `DomainCalendarManagementGateway` calls the same `CreateAcademicYear` / `UpdateAcademicYear` actions as the API | Calendar administration suite | No delete/archive action was specified; none added. |
| Academic period create/edit | TESTED | Same management form and period table | `POST /api/academic-years/{academicYear}/periods`, `PUT\|PATCH /api/academic-periods/{academicPeriod}` | Same adapter calls `CreateAcademicPeriod` / `UpdateAcademicPeriod`; selected year is authoritative | Calendar administration suite | Source, `Y-m-d` dates and configured option lists are enforced by the existing FormRequests. |
| Calendar integrity panel | TESTED | “Calendar check” panel with issues list | `GET /api/academic-calendar/validation` | Same adapter calls `ValidateAcademicCalendar` in process | Test asserts a configured issue is displayed | Integrity rules remain configurable engineering safeguards, not compliance claims. |
| Server-authoritative tenant context | TESTED | `ResolveSchoolContext`, `AppServiceProvider`, `CalendarManagementGateway` | Existing school context middleware | Resolver is persistent Livewire middleware; remembered school ID is revalidated against current memberships on every update | Three dedicated regression tests | No client-supplied `school_id` is trusted; no new school switcher was invented. |
| Accessible form and table primitives | TESTED | `components/select.blade.php`, `components/table.blade.php` | None | `aria-invalid` / `aria-describedby` wired to field errors; table no longer nests an inner card | Design lint + rendered inspector | No known issue. |

`CalendarAdministrationTest.php` contains 12 tests / 30 assertions covering guest redirect, scoped screen rendering, year and period create/edit, inline validation, integrity issues, Livewire context persistence, stale Livewire context rejection and rejection of another school's records.

## Stage B–O — school modules

All modules are `NOT STARTED` unless listed below. A navigation entry is not a claim that a module is available; planned entries lead to an explicit "not built yet" notice.

| Module | Status | Screens | API dependencies | Integration | Tests | Next task |
| --- | --- | --- | --- | --- | --- | --- |
| School identity / profile | TESTED (read-only) | School profile | `GET /api/school`, `GET /api/school/identifiers` | Server-side read adapter | Frontend suite | Stage B may add mutation flows after specification approval. |
| Academic calendar | TESTED (read + write) | Academic calendar list + management | `GET /api/academic-years`, `GET /api/academic-years/current`, `GET /api/academic-years/{id}/periods`, `POST /api/academic-years`, `PUT\|PATCH /api/academic-years/{id}`, `POST /api/academic-years/{id}/periods`, `PUT\|PATCH /api/academic-periods/{id}`, `GET /api/academic-calendar/validation` | Domain adapter over verified in-branch actions; no client `school_id` | 12 Stage B tests / 30 assertions; full suite green | Awaits a product specification for any further workflow. |
| Admissions, learners, guardians, enrolment, academics, attendance, finance, staff, welfare, boarding, transport, operations, library, activities, communication, governance, parent/learner portals | NOT STARTED | — | Not verified in this branch | None | — | Requires an approved specification and endpoint contract in this branch. |

## Stage B browser evidence (2026-11-27)

Recorded against a local `php artisan serve` instance on `127.0.0.1:8010`, backed by a temporary SQLite database, with `FRONTEND_DEMO=false` and freshly built assets.

- Signed in through `POST /api/auth/login`, reached `/app`, opened the read-only academic calendar, then followed “Manage calendar” to `/app/academic-calendar/manage`.
- The management page rendered the seeded `2027` year, its `Term 1` period, both forms and the “Run check” action.
- “Run check” produced `No issues found for 2027.` after a `POST /livewire/update` round trip.
- Created `Term 2` (`2027-05-01` – `2027-08-07`); the UI showed `Period added.` and the new row in the scoped period table. After rebasing onto merged `main`, `Term 3` (`2027-09-01` – `2027-12-10`) was created the same way.
- The first smoke exposed a real defect: Livewire replayed middleware in an inner pipeline, clearing the school context before the component action and returning `403`. The fix registers `ResolveSchoolContext` as persistent Livewire middleware and defers context cleanup until request termination for update requests. A remembered selection is rechecked against the user's memberships and can never widen access; if that membership is removed, the update fails closed instead of falling back to another school. These behaviours have regression tests.
- The rebased smoke exposed a second defect inherited from merged foundation code: an unescaped quote inside the app shell's Alpine `x-data` focus-trap selector stopped Livewire handlers from initialising. The selector now uses a template literal with no nested double quotes, and a frontend regression test asserts the parseable form.
- The final browser pass showed `No issues found for 2027.`, `Period added.`, no console errors and no `403`; logout returned to `/login`.
- The rendered management page was inspected at 1440, 768 and 390 px with `--app --allow font-not-dealt`: 0 findings after stacking the cards to remove an initial `panel-void` / `ragged-row` warning. Screenshots were reviewed at desktop and mobile widths during the original smoke; the rebased page returned the same 0-finding result.

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

Conclusion: the measured client payload is small and the server-rendered pages need no extra round trip on first load. The evidence supports the accepted stack; Inertia is not required for Stage A or Stage B.

## Verification log

All checks below were run locally on 2026-11-27. No remote GitHub Actions result is claimed. The first three entries are the pre-frontend baseline; the `make verify` baseline ran against the original checkout and is not evidence for this worktree. The worktree was verified with the equivalent isolated Compose command recorded below.

| Date | Command | Exit code | Evidence |
| --- | --- | --- | --- |
| 2026-11-27 | `make verify` | 0 | 96 tests, 352 assertions passed (pre-frontend baseline, original checkout) |
| 2026-11-27 | `docker compose config -q` | 0 | No output (baseline) |
| 2026-11-27 | `npm run build` | 0 | 3 modules; CSS 55.21 kB / gzip 11.73 kB; JS 0.00 kB / gzip 0.02 kB (baseline) |
| 2026-11-27 | `npm ci` | 0 | Dependencies installed from `package-lock.json` |
| 2026-11-27 | `php artisan test` | 0 | 129 passed, 440 assertions (Stage A worktree) |
| 2026-11-27 | `./vendor/bin/pint --test` | 0 | `{"tool":"pint","result":"passed"}` |
| 2026-11-27 | `./vendor/bin/phpstan analyse --no-progress --memory-limit=1G` | 0 | `{"tool":"phpstan","result":"passed","errors":0}` |
| 2026-11-27 | `composer audit --locked` | 0 | No security vulnerability advisories found |
| 2026-11-27 | `npm audit --audit-level=high` | 0 | found 0 vulnerabilities |
| 2026-11-27 | `npm run build` | 0 | 4 modules; CSS 46.78 kB / gzip 11.26 kB; JS 1.56 kB / gzip 0.85 kB; stable hash across two builds (Stage A) |
| 2026-11-27 | `docker compose config -q` | 0 | No output |
| 2026-11-27 | `make COMPOSE="docker compose -p zimschool-fe-verify" verify` | 0 | Isolated Compose project with this worktree bind-mounted: Pint, PHPStan, Composer audit and 129 tests / 440 assertions passed (Stage A) |
| 2026-11-27 | `node .../lint.mjs resources/views resources/css resources/js --app` | 0 | 0 FAIL, 0 WARN (Stage A) |
| 2026-11-27 | `node .../inspect.mjs <dashboard snapshot> --app --allow font-not-dealt` | 0 | 0 findings at 1440, 768 and 390 px |
| 2026-11-27 | `node .../inspect.mjs http://127.0.0.1:8010/login --app --allow font-not-dealt` | 0 | One accepted `hero-void` warning at 1440 px |
| 2026-11-27 | `php artisan test tests/Feature/Frontend/CalendarAdministrationTest.php` | 0 | 12 passed, 30 assertions |
| 2026-11-27 | `php artisan test tests/Feature/Frontend` | 0 | 23 passed, 70 assertions (includes the Alpine expression regression test) |
| 2026-11-27 | `npm run build` (final Stage B assets on merged `main`) | 0 | 4 modules; `public/build/manifest.json` 0.33 kB / gzip 0.16 kB; CSS 47.81 kB / gzip 11.43 kB; JS 1.63 kB / gzip 0.87 kB |
| 2026-11-27 | `npm audit --audit-level=high` (final Stage B dependencies) | 0 | found 0 vulnerabilities |
| 2026-11-27 | `make COMPOSE="docker compose -p zimschool-fe-verify" verify` (final Stage B code on merged `main`) | 0 | Pint 158 files PASS; PHPStan no errors; Composer audit no advisories; 193 tests / 708 assertions passed |
| 2026-11-27 | `docker compose config -q` (final Stage B code) | 0 | No output |
| 2026-11-27 | `node .../lint.mjs resources/views resources/css resources/js --app` (final Stage B code) | 0 | 0 FAIL, 0 WARN |
| 2026-11-27 | `node .../inspect.mjs <calendar management snapshot> --app --widths 1440,768,390 --allow font-not-dealt` | 0 | 0 findings; initial `panel-void` and `ragged-row` warnings resolved by stacking cards, result reconfirmed after rebasing onto merged `main` |
| 2026-11-27 | `php artisan route:list --path=api/academic-structure --json` (after PR #59 merge) | 0 | 16 route objects; all under authenticated school context with `EnsurePermission` |
| 2026-11-27 | `make COMPOSE="docker compose -p zimschool-fe-verify" verify` (after PR #59 merge) | 0 | Pint 207 files PASS; PHPStan no errors; Composer audit no advisories; 243 tests / 990 assertions passed |

## GitHub checks observed for PR #56

Recorded from `gh pr checks 56` for head commit `cec1f03`. These are remote GitHub results, distinct from the local commands above.

| Check | Observed result | Evidence |
| --- | --- | --- |
| `docker` | PASS | GitHub Actions run `46049550115`, job `140677167720` |
| `quality` | PASS | GitHub Actions run `46049550115`, job `140677167871` |
| `tests` | PASS | GitHub Actions run `46049550115`, job `140677167387` |
| `GitGuardian Security Checks` | PASS | `https://dashboard.gitguardian.com` |
| CodeRabbit | PENDING at observation | Review in progress |

PR #56 was merged into `main` as commit `a466d17` by `PraiseTechzw` at `2026-11-27T18:28:54Z`. The agent did not merge it or push to `main`.

## Awaiting contracts

- School Settings, Role Assignment and Academic Structure HTTP contracts are verified on this base but have no frontend UI in Stage B; Audit endpoints remain unverified here. Contracts seen in unmerged backend work are deliberately not imported or invented here.
- Role-assignment and Academic Structure endpoints enforce backend permissions (`roles.view` / `roles.manage` and `academic_structure.view` / `academic_structure.manage`), but no verified frontend permission map exists. UI visibility is never treated as authorisation; backend authorization remains authoritative.
- No further school module can be started without an accepted specification and an endpoint contract present in this branch.
