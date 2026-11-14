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

## Code Review Agent Guidelines
Stack guidance: follow the repository-level guidance in [../AGENTS.md](../AGENTS.md) for consistent review practices and expectations.

Language Specific rules are in `.github/instructions/`: 
- PHP/Laravel: `php-laravel.instructions.md`
- JavaScript/TypeScript: `javascripts.instructions.md`

### Principles
- Review only changed lines and code they affect. Read surrounding code first.
- Priority: security > correctness/data integrity > performance > maintainability > style.
- Skip what Pint, Larastan/PHPStan, ESLint, and Prettier already enforce, unless bypassed (new ignores, baseline entries, `eslint-disable`).
- Never invent APIs or framework behavior. If unsure, label it "possible issue" or ask a question.
- Every finding states what is wrong, why it matters, and a concrete fix.
- Group repeated issues into one comment. Max 3 nits; none if blockers exist.
- Always provide context for your comments, linking to relevant code, documentation, or standards when applicable.

## Severity
- 🔴 Blocker: security hole, data loss, broken behavior, unsafe migration
- 🟠 Major: likely bug, N+1, missing authorization/validation, race condition
- 🟡 Minor: maintainability, missing types, duplication
- 🔵 Nit: optional polish
- ❓ Question: intent unclear

### Code Review Agent Instructions
- Focus on module-first architecture adherence.
- Ensure auth and app logic is consistent with existing Core abstractions.
- Validate that new code aligns with the `Modules\...` namespaces.
- Check that documentation and routes reflect the current architecture accurately.
- Verify that tests cover new functionality and follow the module-first structure.
- Ensure that error handling and edge cases are properly addressed in the new code.
- Confirm that new code follows established coding standards and conventions.
- Ensure that new code adheres to established module logging mechanisms and practices.

## Do not

- Do not introduce ad hoc auth or app logic outside the module-first architecture.
- Do not add unrelated refactors.
- Do not leave docs or route examples inconsistent with the current architecture.
- Do not introduce code that bypasses established Core abstractions.
- Do not add tests that do not follow the module-first structure.
- Do not introduce code that violates established error handling practices.
- Do not bypass module boundaries or Core abstractions for convenience.
- Do not introduce code that creates hidden dependencies between modules.
- Do not circumvent established validation and testing practices.
- Do not introduce code that conflicts with existing module-first architecture conventions.
- Do not introduce code that bypasses established Core service contracts.
- Do not introduce code that violates the module-first architecture principles.
- Do not introduce code that creates tight coupling between modules.
- Do not introduce code that bypasses established module boundaries.
- Do not introduce code that violates the separation of concerns between modules.
- Do not introduce code that creates circular dependencies between modules.
- Do not introduce code that violates established module dependency rules.
- Do not introduce code that creates hidden dependencies on external packages or services.
- Do not introduce code that violates established module initialization and bootstrapping procedures.
- Do not introduce code that bypasses established module configuration settings.
- Do not introduce code that violates established module configuration conventions.
- Do not introduce code that bypasses established module initialization procedures.
- Do not introduce code that violates established module lifecycle hooks.
- Do not introduce code that bypasses established module dependency injection practices.
- Do not introduce code that violates established module service contracts.
- Do not introduce code that bypasses established module event handling mechanisms.
- Do not introduce code that violates established module event handling practices.
- Do not introduce code that bypasses established module logging mechanisms.
- Do not introduce code that bypasses established module caching mechanisms.
- Do not introduce code that violates established module caching practices.
