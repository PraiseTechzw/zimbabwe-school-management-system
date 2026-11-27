# Engineering conventions

## PHP

- Laravel Pint (`pint.json`, Laravel preset) is the only formatter. CI runs `pint --test`; never hand-format against it.
- Declare `declare(strict_types=1);` in new PHP files.
- PSR-4 autoloading: classes live where their namespace says they live.
- Controllers stay thin: validate with Form Requests, delegate to a domain action, return a response.
- Prefer explicit types and small actions over large service classes.
- Domain code must not assume another domain's table names; call its public actions/services.

## Database

- All schema changes are migrations; no manual production SQL.
- Migrations are additive where possible; use expand/contract across releases to avoid breaking a rolling deploy.
- Index foreign keys and columns used in filters. Never store secrets or unencrypted personal data in logs.

## Tests

- Pest for unit and feature tests. Feature tests use SQLite in memory.
- Every bug fix includes a regression test. Every new public action has a test.
- Test names describe behaviour (`it('rejects an invalid payload')`), not implementation.

## Security and privacy

- Never commit `.env`, keys, tokens, learner personal data or production dumps.
- Validate all input; authorise every action; use Laravel's password/crypto/session facilities rather than custom code.
- Log events, not sensitive payloads. Report vulnerabilities per [SECURITY.md](../../SECURITY.md).
- Dependency updates run through `composer audit` and Dependabot PRs.

## Documentation and changes

- Behavioural or architectural changes update the relevant docs in the same PR.
- Use the [feature](../../docs/templates/feature-specification.md), [module](../../docs/templates/module-specification.md) and [change request](../../docs/templates/change-request.md) templates for significant work.
- Do not introduce microservices or new major dependencies without an accepted ADR.

## Local commands

```bash
composer lint          # formatting check
composer analyse       # PHPStan + Larastan
composer security-audit
composer test
make verify            # all of the above via Docker
```
