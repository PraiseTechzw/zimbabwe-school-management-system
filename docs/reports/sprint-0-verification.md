# Sprint 0 verification evidence

Date: 2026-11-27. All outputs below are copied from actual terminal runs. Secrets and personal data are redacted or absent.

## Toolchain and versions

```text
git version 2.43.0
PHP 8.3.6 (cli)
Composer version 2.7.1
Docker version 29.1.3
Docker Compose version 2.40.3
gh version 2.45.0
opencode v2.4.1
Laravel Framework 13.44.1
```

Installed dev packages (from `composer.lock`): Pest 4.7.8, pest-plugin-laravel 4.1.0, PHPUnit 12.5.33, Larastan 3.15.0, PHPStan 2.3.7, Pint 1.32.3.

## Composer validation

```text
$ composer validate --strict
./composer.json is valid
EXIT=0
```

## Local quality gates

```text
$ ./vendor/bin/pint --test
PASS 27 files
EXIT=0

$ ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
[OK] No errors
EXIT=0

$ composer audit --locked
No security vulnerability advisories found.
EXIT=0

$ composer test
{"tool":"pest","result":"passed","tests":4,"passed":4,"assertions":5,"duration_ms":964}
EXIT=0
```

## Docker build, boot and validation

```text
$ docker compose config -q
COMPOSE_CONFIG_VALID / EXIT=0

$ docker compose build app
app  Built
EXIT=0

$ docker compose up -d
Container zimbabwe-school-management-system-mysql-1  Healthy
Container zimbabwe-school-management-system-app-1     Healthy
Container zimbabwe-school-management-system-nginx-1   Started
EXIT=0

$ docker compose ps
NAME                                        STATUS
zimbabwe-school-management-system-app-1     Up (healthy)
zimbabwe-school-management-system-mysql-1   Up (healthy)
zimbabwe-school-management-system-nginx-1   Up (healthy)

$ docker compose exec -T app php artisan --version
Laravel Framework 13.44.1
EXIT=0

$ docker compose exec -T app php artisan migrate --force
Creating migration table ................................................. 147.50ms DONE
0001_01_01_000000_create_users_table ..................................... 847.99ms DONE
0001_01_01_000001_create_cache_table ..................................... 452.35ms DONE
0001_01_01_000002_create_jobs_table ...................................... 655.18ms DONE
EXIT=0

$ curl -s -o /dev/null -w 'HTTP_STATUS=%{http_code}\n' http://localhost:8000/
HTTP_STATUS=200
```

## Full Docker verification loop (`make verify`)

```text
$ make verify
docker compose exec -T app ./vendor/bin/pint --test
  PASS 27 files
docker compose exec -T app ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
  [OK] No errors
docker compose exec -T app composer audit --locked
  No security vulnerability advisories found.
docker compose exec -T app php artisan test
  PASS Tests\Unit\FoundationTest
  PASS Tests\Feature\FoundationTest
  Tests: 4 passed (5 assertions)
VERIFY_EXIT=0
```

## Configuration syntax validation

```text
YAML_OK docker-compose.yml
YAML_OK .github/dependabot.yml
YAML_OK .github/workflows/ci.yml
YAML_OK .github/ISSUE_TEMPLATE/{bug_report,feature_request,research_task,config}.yml
AGENT_OR_COMMAND_OK: 8 agents + 8 commands (all parsed with expected frontmatter keys)
LINKS_CHECKED=46
MISSING=0
VALIDATION_EXIT=0
```

## Security checks

```text
$ grep -RInE '(gho_|ghp_|sk-...|PRIVATE KEY|password=...)' --exclude-dir=.git --exclude-dir=vendor .
./docker-compose.yml: test: ["CMD-SHELL", "mysqladmin ping ... --password=\"$$MYSQL_ROOT_PASSWORD\" ..."]
```

The only match is an environment-variable reference, not a credential. `composer audit --locked` reported no advisories.

On the first PR run, the repository's GitGuardian check reported two secret-shaped patterns: a `DB_USERNAME`/`DB_PASSWORD` pair in `.env.example` and a Laravel `APP_KEY` in `.env.testing`. Both were non-production placeholders, but the patterns were removed rather than dismissed:

```text
$ grep -RInE 'APP_KEY=base64:|DB_PASSWORD=[A-Za-z0-9_-]{8,}' \
    --exclude-dir=.git --exclude-dir=vendor --exclude-dir=node_modules --exclude='.env' .
(no matches)

$ composer test
{"tool":"pest","result":"passed","tests":4,"passed":4,"assertions":5}
EXIT=0

$ make verify
Pint PASS 28 files; PHPStan [OK] No errors; audit clean; Pest 4 passed (5 assertions)
VERIFY_EXIT=0
```

- Database credentials now come from Compose `${VAR:-default}` injection and are absent from committed `.env.example`.
- `APP_KEY` is generated per run in `tests/bootstrap.php` (32 random bytes), so no key-shaped value exists in the repository.

## GitHub configuration (actually verified with `gh`)

```text
$ gh label list --repo PraiseTechzw/zimbabwe-school-management-system --limit 100
bug, enhancement, research, documentation, security, good first issue, help wanted,
dependencies, automated, ci, sprint-0, blocked, needs-evidence, needs-review
+ GitHub default labels (accessibility, duplicate, invalid, question, wontfix)

$ gh api repos/.../branches/main/protection
{
  "approvals": 1,
  "conversation_resolution": true,
  "deletions": false,
  "dismiss_stale": true,
  "enforce_admins": true,
  "force_pushes": false,
  "required_checks": ["quality", "tests", "docker"],
  "strict": true
}

$ gh api repos/...
{
  "allow_merge_commit": false,
  "allow_rebase_merge": false,
  "allow_squash_merge": true,
  "delete_branch_on_merge": true,
  "default_branch": "main",
  "has_issues": true,
  "has_projects": true,
  "visibility": "public"
}
```

Repository created with an initial README commit generated by GitHub. All Sprint 0 work is on branch `chore/sprint-0-foundation` and submitted through a PR; no direct push to `main` was performed. The PR is left for human review and is not auto-merged.

A GitHub Project board was **not** created; `docs/github/project-board.md` contains the configuration guidance.

## Remote CI status

Final PR run on the squashed `chore/sprint-0-foundation` commit, verified with `gh pr checks 1 --watch`:

```text
GitGuardian Security Checks  pass  1s
CodeRabbit                   pass  0s   (review skipped: 114 files exceed its 100-file limit)
docker                       pass  1m38s
quality                      pass  16s
tests                        pass  20s
CHECKS_EXIT=0
```

All three branch-protection jobs (`quality`, `tests`, `docker`) passed, and the GitGuardian secret scan passed. CodeRabbit skipped its review because the diff exceeds its 100-file limit; that is informational, not a failure.

Earlier runs are recorded for transparency:

```text
9d051f6: GitGuardian fail (2 placeholder patterns in .env.example / .env.testing)
a1f597d: GitGuardian fail (incidents still attached to the earlier PR commit)
squashed commit: GitGuardian pass (single commit; no secret-shaped value in any PR commit)
```

To clear the stale incidents, the feature branch history was squashed to one commit and force-pushed with `--force-with-lease`. **No force-push was made to `main`.** The PR is still open and unmerged, awaiting human review.

## Issues found and resolved during verification

1. `php artisan test` read `DB_CONNECTION=mysql` from `.env`, failing the SQLite test. Fixed with committed `.env.testing`, forced test env values, and removing Compose injection of `.env` into the app process.
2. Inherited Composer `test` script contained the invalid placeholder `@no_additional_args`. Removed; script now calls `@php artisan test`.
3. nginx healthcheck probed a non-existent `/health` route and stayed unhealthy. Changed to probe `/`; status became healthy and HTTP returned 200.
4. GitGuardian flagged credential-shaped placeholders in `.env.example` and `.env.testing`. Removed both patterns; the test key is generated at runtime in `tests/bootstrap.php` and DB credentials are injected by Compose. Because the scanner tracked the original commit, the branch was squashed to a single clean commit; all checks then passed.
