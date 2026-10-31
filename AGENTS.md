# AGENTS.md

This repository is a Laravel application that has been migrated to a module-first architecture. These instructions are for any AI assistant or contributor working in this codebase.

## 1) Project overview

- Primary framework: Laravel 13
- PHP requirement: ^8.2
- Frontend build: Vite + Laravel asset pipeline
- App namespace: `Modules\...` for module code; root Laravel app is kept minimal
- Core module: `modules/Core/` is the foundation for shared services, auth, models, middleware, and presentation code

## 2) Architectural rules

### Module-first structure

- Keep new business behavior inside modules rather than spreading logic across the root `app/` directory.
- The module root is configured in `config/modules.php` and routes are auto-discovered from:
  - `modules/<ModuleName>/routes/web.php`
  - `modules/<ModuleName>/routes/api.php`
- Use the module manifest file `module.php` when a module needs provider registration.

### Core boundaries

For shared application logic, follow the layer pattern established in `modules/Core`:

- `Domain/...` for contracts, value objects, and domain rules
- `Application/...` for orchestration and services
- `Infrastructure/...` for framework adapters and integrations
- `Presentation/...` for controllers, middleware, and request handling

When working with auth, impersonation, notifications, or app configuration, prefer the Core abstractions and service managers already in place instead of introducing ad hoc logic.

### Shared conventions

- Prefer dependency injection over static access or direct framework bootstrapping.
- Reuse existing contracts, DTOs, and managers before creating duplicates.
- Keep route definitions in the appropriate module route file or in the root `routes/web.php` when the route is app-wide.
- Do not bypass `AuthenticationManager`, `ImpersonationManager`, or analogous Core service APIs when a feature already uses them.
- When adding admin or authenticated functionality, follow the existing middleware flow (`auth`, `auth.type`) and route patterns already used in the app.

## 3) Local setup

Use the project setup commands from the Laravel app and maintain the current module architecture:

```bash
composer install
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

Common validation commands:

```bash
composer test
php artisan test
php artisan module:list
php artisan module:list --json
```

The repository also supports a combined app bootstrap script:

```bash
composer run setup
```

## 4) Working rules for AI agents

### Before making changes

- Inspect the most relevant module, controller, and domain/service file before editing.
- Prefer targeted, surgical modifications over broad rewrites.
- Match the existing coding style and naming patterns used in the project.
- Check whether a feature already exists in `modules/Core` or another module before creating a new implementation.

### After changes

- Update documentation when behavior, routes, environment variables, or architecture changes.
- Keep examples consistent with the current module-first architecture (`Modules\...`).
- Validate with the smallest relevant command that checks the behavior.
- Avoid touching generated files, vendor code, lockfiles, or unrelated legacy code unless the task requires it.

## 5) Feature and code expectations

### Authentication and authorization

- Authentication behavior is centralized in the Core module.
- The canonical auth configuration API is `Modules\Core\Application\Auth\AuthenticationManager`.
- Keep auth mode checks and role-based access consistent with the existing middleware and controllers.
- If a feature changes login, logout, CAS, or auth type behavior, update the relevant docs and route behavior together.

### Modules and new code

When creating a new module:

```bash
php artisan module:make Billing
php artisan module:make Billing --email-template=welcome-user
```

A module should include the structure expected by the current project:

- `Application/`
- `Domain/`
- `Infrastructure/`
- `Presentation/`
- `routes/`
- `module.php`
- `Providers/` when needed

### Routes and middleware

- Keep root-level app routes in `routes/web.php` only when they are truly global.
- Prefer module-local route files for module-specific HTTP endpoints.
- Follow the current middleware alias conventions and do not introduce duplicate auth checks in controller logic when middleware already handles them.

## 6) Testing and validation standards

- Run the smallest relevant test suite for the changed behavior.
- Prefer `php artisan test` for feature or unit validation.
- For frontend changes, run the relevant Vite build or asset validation command when needed.
- If a change alters a route, controller, auth flow, or module boundary, verify the corresponding behavior end-to-end or with focused tests.

## 7) Documentation expectations

Project documentation is maintained under `docs/` and the root `README.md` is intended to be a short entry point. When a change affects architecture, auth, API behavior, or setup, update the appropriate guide in `docs/` in the same change set.

## 8) Non-goals / avoid these patterns

- Do not add a second auth system or duplicate app config logic.
- Do not create legacy-style code outside the module architecture when a module boundary exists.
- Do not add broad refactors unrelated to the task.
- Do not bypass established Core services or contracts just to make a quick fix.
- Do not leave stale or contradictory docs when behavior changes.

## 9) Quick reference

```bash
# setup
composer install
npm install
php artisan migrate

# app runtime
php artisan serve
npm run dev

# module inspection
php artisan module:list
php artisan module:list --json

# tests
php artisan test
```

These instructions are intentionally general so they can be followed by different AI assistants while still matching this repository's current module-based Laravel architecture.
