---
applyTo: "**/*.php"
---
# PHP 8.5+ / Laravel 13+ Review Rules

## Security
- `whereRaw`/`orderByRaw`/`DB::raw` never interpolate user input; allowlist user-supplied column names.
- No `unserialize` on untrusted data; no `eval`, `exec`, `shell_exec`, or backticks with user input.
- File uploads: validate type/size/extension, never trust client MIME or filename, store privately unless intended.
- Rate limiting on login, password reset, and other sensitive endpoints.
- CSRF exclusions must be justified. No sensitive data in URLs or logs.

## Laravel
- Thin controllers: business logic belongs in actions/services/models.
- Eloquent: catch N+1 (missing `with()`, lazy loading in loops or Resources); use `chunkById`/`lazy`/`cursor` for large sets; `exists()` over `count() > 0`; `select()` on heavy queries.
- Use `DB::transaction()`; dispatch jobs/events after commit where order matters (`afterCommit`).
- Race conditions: `lockForUpdate`, unique constraints, atomic increments; beware `firstOrCreate`/`updateOrCreate` under concurrency.
- Jobs: idempotent, sensible `tries`/`backoff`/`timeout`, implement `failed()`.
- Migrations: reversible if the project expects it, indexes for new query patterns, indexed foreign keys, backfill plan for destructive changes.
- Use API Resources, correct HTTP status codes, and pagination on list endpoints.
- Avoid heavy work or hidden side effects in observers/listeners.
- Tests (Pest/PHPUnit): use factories; fake `Http`, `Queue`, `Mail`, and time (`Carbon::setTestNow()`); no order-dependent tests.
- Flag APIs deprecated or removed in Laravel 13. If unsure whether something changed, cite the upgrade guide rather than guessing.

## PHP 8.5+
- `declare(strict_types=1);` per project convention.
- Full type coverage; avoid `mixed` without reason. Prefer backed enums, `readonly` classes/properties, and DTOs/value objects over loose arrays crossing boundaries.
- Prefer modern constructs where clearer: constructor promotion, `match`, first-class callables, `array_first()`/`array_last()`, `clone` with property overrides, pipe operator `|>` (only when more readable), `#[\Override]`, `#[\NoDiscard]` for must-use return values.
- Flag deprecated behavior: backtick operator, non-canonical casts (`(boolean)`, `(integer)`, `(double)`, `(binary)`), `null` as an array offset, `__sleep`/`__wakeup` (use `__serialize`/`__unserialize`).
- No `@` suppression, `extract()`, `global`, or dynamic properties.
- Use `===`; watch `in_array` without strict flag and `empty()` where `0`/`"0"` are valid.
- Flag new PHPStan/Larastan baseline entries or `@phpstan-ignore`.