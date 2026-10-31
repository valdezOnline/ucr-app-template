# Copilot Instructions

Use the repository-level guidance in [../AGENTS.md](../AGENTS.md) as the canonical contributor instructions for this project.

## Project summary

- Laravel 13 application with a module-first architecture.
- Shared services live in `modules/Core/`.
- Keep new logic aligned with `Modules\...` namespaces and the existing Core abstraction layers.
- Use the smallest validation command that checks the relevant behavior.

## Working expectations

- Prefer `modules/Core` contracts and managers before creating new one-off services.
- Keep route and middleware changes consistent with the existing `auth` and `auth.type` flow.
- Update docs in `docs/` whenever behavior, setup, or route changes.
- Validate with `php artisan test` or the smallest targeted command relevant to the change.

## Architecture notes

- Module root: `config/modules.php`
- Module route discovery: `modules/<ModuleName>/routes/web.php` and `routes/api.php`
- Core boundaries: `Domain`, `Application`, `Infrastructure`, `Presentation`
- Shared auth entry point: `Modules\Core\Application\Auth\AuthenticationManager`

## Do not

- Do not introduce ad hoc auth or app logic outside the module-first architecture.
- Do not add unrelated refactors.
- Do not leave docs or route examples inconsistent with the current architecture.
