<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Authentication Configuration

This application supports flexible authentication with the ability to toggle between two methods:

### Laravel Authentication (Default)
- Traditional email/password authentication
- Local user registration and management
- Password reset functionality

### CAS Authentication (Single Sign-On)
- Central Authentication Service integration
- Single Sign-On capability
- No local password management required

### Configuration

Set the authentication type in your `.env` file:

```env
# Options: 'laravel' (default) or 'cas'
AUTH_TYPE=laravel
```

**Admin users can also change this via the web interface at `/admin/auth-config`**

For detailed configuration instructions, see [AUTHENTICATION_GUIDE.md](AUTHENTICATION_GUIDE.md).

## Modular Architecture (Phase 1 Scaffold)

This project now includes a non-breaking modular scaffold so new domains can be added without changing the core bootstrap flow.

- Module root path is configured in `config/modules.php`
- Module routes are auto-discovered from:
  - `modules/<ModuleName>/routes/web.php`
  - `modules/<ModuleName>/routes/api.php`
- Optional module service providers can be declared in:
  - `modules/<ModuleName>/module.php`

Current application behavior remains in the existing app/routes structure, and this scaffold is intended for incremental migration.

### Phase 2 (Core namespace carve-out)

The `Core` module now contains initial layered namespaces and non-breaking adapters:

- `modules/Core/Domain/...` for module contracts
- `modules/Core/Application/...` for orchestration services
- `modules/Core/Infrastructure/...` for adapters to existing `App\Services\...`
- `modules/Core/Providers/CoreServiceProvider.php` for dependency bindings

This does not replace existing application classes yet; it introduces stable module contracts and maps them to the current implementations to enable gradual migration.

### Phase 3 (initial consumer migration)

Initial HTTP consumers now resolve Core application services through dependency injection:

- `AuthConfigController` now uses `Modules\Core\Application\Auth\AuthenticationManager`
- `AdminImpersonationController` now uses `Modules\Core\Application\Impersonation\ImpersonationManager`
- `SiteStatusController` now uses `Modules\Core\Application\Auth\AuthenticationManager`
- `Auth\LoginController` now uses both Core managers for auth-mode and impersonation flows
- `Api\AuthController` now resolves auth type for health response via `AuthenticationManager`

These managers still delegate to legacy service implementations through Core infrastructure adapters, preserving existing behavior while moving call sites behind module contracts.

### Phase 4 (service internals moved behind Core contracts)

`App\Services\AuthenticationService` and `App\Services\ImpersonationService` now act as compatibility wrappers that resolve Core contracts from the container:

- `Modules\Core\Domain\Auth\Contracts\AuthenticationGateway`
- `Modules\Core\Domain\Impersonation\Contracts\ImpersonationGateway`

This keeps existing static call sites working while shifting implementation ownership into the Core module infrastructure.

### Phase 5 (middleware and views aligned to Core managers)

Authentication-related middleware and key auth views now consume Core-managed state instead of calling static service methods directly:

- `AuthenticationTypeMiddleware` now uses injected Core managers
- `admin/auth-config` view uses controller-provided values
- `auth/login` view uses shared Core-derived view variables from `AppServiceProvider`

### Phase 6 (CAS route closures migrated)

CAS login/logout route closures in `routes/web.php` were moved into `CasAuthController` actions so those flows also run through DI-resolved Core managers rather than static service calls.

### Phase 7 (debug auth routes hardened)

Debug authentication routes were moved from closures to `AuthDebugController`, and are now explicitly restricted to the local environment.

### Phase 8 (Core-first tests and docs)

Authentication tests and guides now use `Modules\Core\Application\Auth\AuthenticationManager` as the primary API. Legacy `App\Services\AuthenticationService` and `App\Services\ImpersonationService` are retained as compatibility wrappers and marked deprecated.

### Phase 9 (shared email dispatcher boundary)

A reusable email dispatch boundary was added for module-to-module use:

- Core contract: `Modules\Core\Domain\Notifications\Contracts\EmailDispatcher`
- Core DTO: `Modules\Core\Domain\Notifications\Data\EmailMessage`
- Notifications module implementation:
  - `Modules\Notifications\Application\Dispatching\QueuedEmailDispatcher`
  - `Modules\Notifications\Jobs\SendEmailJob`
  - `Modules\Notifications\Infrastructure\Email\LaravelEmailSender`

Use the contract via dependency injection so future modules can dispatch email without coupling to provider or queue internals.

### Phase 10 (admin email delivery monitor)

Email delivery logging and admin monitoring were added:

- Delivery log table: `email_dispatch_logs`
- Queue and immediate dispatch paths now persist status transitions (`queued`, `sent`, `failed`)
- Admin UI:
  - `admin/email-dispatches` (list + filters)
  - `admin/email-dispatches/{emailDispatchLog}` (detail view)
  - `admin/email-dispatches/{emailDispatchLog}/retry` (retry failed dispatch)

### Phase 11 (module scaffolding artisan command)

Use Artisan to scaffold a new module architecture:

```bash
php artisan module:make Billing
```

Optional email template scaffolding:

```bash
php artisan module:make Billing --email-template=welcome-user
```

This command creates module folders for application/domain/infrastructure/controllers/routes/views/providers and wires a module manifest (`module.php`) compatible with module auto-discovery.

### Phase 12 (module inspection artisan command)

List discovered modules and scaffold metadata:

```bash
php artisan module:list
```

JSON output (for automation/scripts):

```bash
php artisan module:list --json
```

## Site Status Modes (Admin Controlled)

Administrators can place the full website into one of the following status modes from `/admin/site-status`:

- **Maintenance Mode**
- **Experiencing Technical Issue**
- **Customizable Message**

When any of the three modes is enabled, all guests and non-admin users are redirected to the public status page (`/site-status`) and will see the configured message. Admin users can still access the admin interface to update or disable the mode.

Saving Site Status also automatically updates authentication mode:
- When Site Status is active (`Maintenance`, `Experiencing Technical Issue`, or `Customizable Message`), auth is switched to `laravel`.
- When Site Status is turned off (`Site Available`), auth is switched to `cas`.

## User Dashboard Personalization

Authenticated users can personalize their dashboard:

- **My Applications** tiles are draggable and the tile order can be saved per user.
- **My Shortcuts** allows users to add and remove internal or external links.
- Shortcuts can optionally be assigned a custom category (for example, "Daily" or "Reports").

## Admin User Impersonation

Administrators can temporarily log in as a non-admin user from user management screens.

- Works with both `AUTH_TYPE=laravel` and `AUTH_TYPE=cas`.
- Admins cannot impersonate other admin users.
- While impersonating, a visible warning banner indicates which user is being impersonated.
- Selecting logout during impersonation stops impersonation and returns the admin to their own account.
- All impersonation sessions are written to the `impersonation_audits` database table.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
