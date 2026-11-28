# Learner Management foundation — local verification evidence

**Branch:** `feature/backend-learner-management-foundation`
**Feature commit:** `e210ece` (`feat(learner-management): add school-scoped foundation`)
**Merged base:** `origin/main` `5e5c726` (includes #63, #64, #65 and the B-06 decision brief #66)
**Status:** implemented and locally verified; still **PROPOSED** until human review and merge. No deployment or remote CI success is claimed.

## Isolation and method

All commands ran from the isolated worktree with `COMPOSE_PROJECT_NAME=zimschool-learner`. The Compose override used ports `8026`/`3326` and subnet `10.99.26.0/24`, separate from the default development stack. The default stack was not used.

The PHP test suite uses the repository's in-memory SQLite test database, as asserted by `Tests\Feature\FoundationTest`. MySQL was used only for the migration and schema probe recorded below. Those are different verification surfaces and are not conflated here.

## Docker and static checks

| Command | Result |
| --- | --- |
| `docker compose -f docker-compose.yml -f <learner override> config -q` | exited `0`; no output |
| `make lint` (`./vendor/bin/pint --test`) | `PASS`, 239 files |
| `make analyse` (`./vendor/bin/phpstan analyse --no-progress --memory-limit=1G`) | `[OK] No errors` |
| `make audit` (`composer audit --locked`) | `No security vulnerability advisories found.` |

## Pest evidence (in-memory SQLite)

| Scope | Result |
| --- | --- |
| Learner suite: `php artisan test tests/Feature/Domains/LearnerManagement` | `35 passed (175 assertions)` |
| Full suite: `make verify` → `php artisan test` | `326 passed (1455 assertions)`, `81.58s` |

The learner suite covers: registration and correction; validation datasets; search, pagination and wildcard escaping; server-owned field rejection; authentication and school context; explicit multi-school selection; cross-school route targets and placement references; generic `404` messages; wildcard-only permission grants and route bindings; privacy-minimised audit snapshots; no audit events for reads or rejected writes; schema, unique reference, composite foreign key and cascade behaviour.

## MySQL migration and schema probe

`php artisan migrate --force` ran 22 migrations on the isolated MySQL database. All reported `DONE`, including `2026_11_28_000006_create_learners_table`. `php artisan migrate:status` reports all 22 migrations as `[1] Ran`.

`information_schema.COLUMNS` for `learners` returned:

| Column | Type | Nullable |
| --- | --- | --- |
| `id` | `bigint unsigned` | no |
| `school_id` | `bigint unsigned` | no |
| `learner_reference` | `varchar(32)` | no |
| `full_name` | `varchar(255)` | no |
| `date_of_birth` | `date` | yes |
| `status` | `varchar(32)` | no |
| `class_offering_id` | `bigint unsigned` | yes |
| `created_at`, `updated_at` | `timestamp` | yes |

`information_schema.KEY_COLUMN_USAGE` joined to `REFERENTIAL_CONSTRAINTS` returned:

| Constraint | Columns → referenced columns | `DELETE_RULE` |
| --- | --- | --- |
| `learners_school_id_foreign` | `school_id` → `schools.id` | `CASCADE` |
| `learners_school_id_class_offering_id_foreign` | `school_id` → `class_offerings.school_id`; `class_offering_id` → `class_offerings.id` | `RESTRICT` |

The restrictive placement FK was chosen after SQLite exposed that `nullOnDelete` on the composite key could null the non-nullable `school_id`. The trade-off and the open lifecycle question are recorded as `LM-D-08` and `LM-U-11`.

## Route probe

`php artisan route:list --path=api/learners --json` returned four routes:

| Method | URI | Middleware permission |
| --- | --- | --- |
| `GET\|HEAD` | `api/learners` | `learners.view` |
| `POST` | `api/learners` | `learners.manage` |
| `GET\|HEAD` | `api/learners/{learner}` | `learners.view` |
| `PUT\|PATCH` | `api/learners/{learner}` | `learners.manage` |

Each route has `web`, `Illuminate\Auth\Middleware\Authenticate` and `App\Http\Middleware\ResolveSchoolContext` before its permission gate. There is no delete, archive, transition or export route.

## Scope and residual risk

- No frontend file was modified. The frontend dependency tracker remains marked **PROPOSED** for learners and must only be promoted to VERIFIED after this PR is reviewed and merged.
- No guardian, medical, attendance, assessment, admissions, enrolment, finance, safeguarding or document field or workflow was added.
- No statutory field catalogue, national identifier, age band, grade/form crosswalk, retention period or role-access rule was encoded as fact.
- `LM-U-01`–`LM-U-11` remain open. Human review and Product Owner/legal decisions are still required for policy questions.
- This report records local evidence only. GitHub Actions results, merge state and deployment state are not claimed here.
