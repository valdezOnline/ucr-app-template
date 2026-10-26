# Modular Architecture

This project has been migrated to a module-based Laravel architecture with `Core` as the base application module.

## Phase 1: Modular Scaffold

- Module root path configured in `config/modules.php`
- Module routes auto-discovered from:
  - `modules/<ModuleName>/routes/web.php`
  - `modules/<ModuleName>/routes/api.php`
- Optional module service providers declared in:
  - `modules/<ModuleName>/module.php`

## Phase 2: Core Namespace Carve-Out

The `Core` module introduced layered boundaries:

- `modules/Core/Domain/...` for module contracts
- `modules/Core/Application/...` for orchestration services
- `modules/Core/Infrastructure/...` for adapters and framework integrations
- `modules/Core/Providers/CoreServiceProvider.php` for dependency bindings

## Phase 3: Initial Consumer Migration

HTTP consumers were moved to Core application services through dependency injection:

- `AuthConfigController` → `Modules\Core\Application\Auth\AuthenticationManager`
- `AdminImpersonationController` → `Modules\Core\Application\Impersonation\ImpersonationManager`
- `SiteStatusController` → `Modules\Core\Application\Auth\AuthenticationManager`
- `Auth\LoginController` → Core auth + impersonation managers
- `Api\AuthController` → `AuthenticationManager`

## Phase 4: Service Internals Behind Core Contracts

Authentication and impersonation behavior are now behind:

- `Modules\Core\Domain\Auth\Contracts\AuthenticationGateway`
- `Modules\Core\Domain\Impersonation\Contracts\ImpersonationGateway`

## Phase 5: Middleware and Views Aligned to Core

- `AuthenticationTypeMiddleware` uses injected Core managers
- `admin/auth-config` view uses controller-provided values
- auth-related view state is shared via `Modules\Core\Providers\AppServiceProvider`

## Phase 6: CAS Route Closure Migration

CAS login/logout route closures were moved into `CasAuthController` actions.

## Phase 7: Debug Auth Hardening

Debug auth routes moved to `AuthDebugController` and restricted to local environment.

## Phase 8: Core-First Tests and Docs

Authentication tests and guides use `Modules\Core\Application\Auth\AuthenticationManager` as the canonical API.

## Phase 9: Shared Email Dispatcher Boundary

Reusable email dispatch boundary for cross-module use:

- Contract: `Modules\Core\Domain\Notifications\Contracts\EmailDispatcher`
- DTO: `Modules\Core\Domain\Notifications\Data\EmailMessage`
- Notifications module implementation:
  - `Modules\Notifications\Application\Dispatching\QueuedEmailDispatcher`
  - `Modules\Notifications\Jobs\SendEmailJob`
  - `Modules\Notifications\Infrastructure\Email\LaravelEmailSender`

## Phase 10: Admin Email Delivery Monitor

- Delivery log table: `email_dispatch_logs`
- Status transitions persisted (`queued`, `sent`, `failed`)
- Admin UI:
  - `admin/email-dispatches`
  - `admin/email-dispatches/{emailDispatchLog}`
  - `admin/email-dispatches/{emailDispatchLog}/retry`

## Phase 11: Module Scaffolding Command

```bash
php artisan module:make Billing
php artisan module:make Billing --email-template=welcome-user
```

The command scaffolds:

- Application / Domain / Infrastructure
- Presentation controllers / routes / views
- Providers
- `module.php` manifest

## Phase 12: Module Inspection Command

```bash
php artisan module:list
php artisan module:list --json
```

## Phase 13: Legacy Code Cutover

Legacy implementation classes migrated into `modules/Core`:

- `modules/Core/Presentation/Http/Controllers`
- `modules/Core/Presentation/Http/Middleware`
- `modules/Core/Models`
- `modules/Core/Services`
- `modules/Core/Providers/AppServiceProvider.php`
- `modules/Core/Providers/ModuleServiceProvider.php`
- `modules/Core/Console/Commands`

Runtime wiring uses Core module classes for routes, middleware aliases, providers, models, and commands.

## Site Status Modes

From `/admin/site-status`, administrators can set:

- Maintenance Mode
- Experiencing Technical Issue
- Customizable Message

When enabled, guests and non-admin users are redirected to `/site-status`.

## User Dashboard Personalization

Authenticated users can:

- Reorder dashboard application tiles
- Add/update/remove shortcut links
- Categorize shortcuts

## Admin User Impersonation

Administrators can impersonate non-admin users:

- Warning banner appears during impersonation
- Logout stops impersonation and returns to admin account
- Audit trail stored in `impersonation_audits`
