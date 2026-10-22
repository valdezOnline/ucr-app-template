# Authentication Configuration Guide

This application supports two primary web authentication methods and an external broker flow:

## Authentication Types

### 1. Laravel Authentication (Default)
- Traditional email/password authentication
- User registration available
- Password reset functionality
- Local user management

### 2. CAS Authentication (Single Sign-On)
- Central Authentication Service integration
- Single Sign-On capability
- No local password management
- User registration disabled (managed centrally)

### 3. External Broker Authentication (CAS + API)
- External application initiates auth through this app
- CAS login is handled by this app
- Access is validated using application API credentials and user role mapping
- External app receives authorization code, then exchanges for Sanctum token

## Configuration

### Environment Variable

Add or modify the following in your `.env` file:

```env
# Authentication Configuration
# Options: 'laravel' (default) or 'cas'
AUTH_TYPE=laravel
```

**Available Options:**

- `laravel` - Use Laravel's built-in authentication (default)
- `cas` - Use CAS (Central Authentication Service) authentication

### Switching Authentication Types

#### To Use Laravel Authentication (Default)
```env
AUTH_TYPE=laravel
```

#### To Use CAS Authentication
```env
AUTH_TYPE=cas
```

**Note:** When using CAS authentication, ensure your CAS configuration is properly set up in the `.env` file:

```env
CAS_HOSTNAME=your-cas-server.com
CAS_URI=/cas
CAS_CLIENT_SERVICE=https://your-app-domain.com/
CAS_LOGOUT_URL=https://your-cas-server.com/cas/logout
CAS_LOGOUT_REDIRECT=https://your-app-domain.com/
# ... other CAS configuration options
```

`CAS_HOSTNAME` should be the host only. Put the CAS path in `CAS_URI`.

## Features by Authentication Type

### Laravel Authentication Features
- ✅ Login form with email/password
- ✅ User registration
- ✅ Password reset functionality
- ✅ Remember me functionality
- ✅ Local user management

### CAS Authentication Features
- ✅ Single Sign-On (SSO)
- ✅ Automatic redirect to CAS login
- ✅ CAS logout integration
- ❌ No local registration (handled by CAS)
- ❌ No password reset (handled by CAS)
- ✅ Masquerade mode for development

## View Changes

The application automatically adapts the UI based on the authentication type:

### Login Page
- **Laravel Auth**: Shows email/password form with registration link
- **CAS Auth**: Shows "Sign In with CAS" button

### Navigation
- **Laravel Auth**: Standard logout form submission
- **CAS Auth**: Direct link to CAS logout

## Middleware

The application includes `AuthenticationTypeMiddleware` that:
- Handles CAS authentication automatically for protected routes
- Redirects unauthenticated users to the appropriate login method
- Manages user session creation for CAS users

## Blade Directives

Custom Blade directives are available for conditional rendering:

```blade
@casauth
    <!-- Content shown only when CAS authentication is enabled -->
@endcasauth

@laravelauth
    <!-- Content shown only when Laravel authentication is enabled -->
@endlaravelauth

@casavailable
    <!-- Content shown only when CAS is properly configured and available -->
@endcasavailable
```

## Helper Service

The `AuthenticationService` provides methods for checking authentication configuration:

```php
use App\Services\AuthenticationService;

// Check authentication type
$type = AuthenticationService::getType(); // 'laravel' or 'cas'

// Check if specific type is enabled
$isLaravel = AuthenticationService::isLaravelAuth();
$isCas = AuthenticationService::isCasAuth();

// Check if CAS is properly configured
$casAvailable = AuthenticationService::isCasAvailable();

// Get appropriate routes
$loginRoute = AuthenticationService::getLoginRoute();
$logoutRoute = AuthenticationService::getLogoutRoute();

// Check feature availability
$canRegister = AuthenticationService::registrationEnabled();
$canResetPassword = AuthenticationService::passwordResetEnabled();
```

## Admin Impersonation

Admin impersonation is supported for both Laravel and CAS authentication modes.

- Admins can impersonate active, non-admin users from user management pages.
- Admins cannot impersonate other admin users.
- While impersonating, a warning banner is displayed with the impersonated user's name.
- Logging out during impersonation stops impersonation and restores the original admin session (instead of fully logging out).
- Each impersonation start/stop event is persisted in `impersonation_audits` for audit history.

## Development

### Testing with Different Authentication Types

1. **Switch to Laravel Auth:**
   ```env
   AUTH_TYPE=laravel
   ```
   Then clear config cache: `php artisan config:clear`

2. **Switch to CAS Auth:**
   ```env
   AUTH_TYPE=cas
   ```
   Ensure CAS configuration is set up, then clear config cache: `php artisan config:clear`

### CAS Masquerade Mode

For development, you can use CAS masquerade mode:

```env
CAS_MASQUERADE=your-test-username
```

This bypasses actual CAS authentication and uses the specified username.
Make sure the masquerade `net_id` exists in your local `users` table and is active.

## External Broker Configuration

The external broker flow is controlled independently of `AUTH_TYPE` so existing web login behavior remains unchanged.

### Environment Options

```env
EXTERNAL_AUTH_BROKER_ENABLED=true
EXTERNAL_AUTH_BROKER_AUTH_TTL=10
EXTERNAL_AUTH_BROKER_CODE_TTL=5
EXTERNAL_AUTH_BROKER_REQUIRE_HTTPS=true
```

### Per-Application Requirements

To allow an external app client:

1. Enable API authentication for the application.
2. Ensure API credentials (`api_key` and `api_secret`) are generated and active.
3. Enable external broker mode for the application.
4. Set the exact callback URL used by the external app.
5. Assign at least one role to the application that eligible users have.

### External Broker Endpoints

1. `GET /api/auth/authorize`
2. `GET /api/auth/callback`
3. `POST /api/auth/token`

See API_AUTHENTICATION_GUIDE.md for the request and response contracts.

## Troubleshooting

### Common Issues

1. **Login page shows wrong authentication method:**
   - Clear config cache: `php artisan config:clear`
   - Verify `.env` file has correct `AUTH_TYPE` value

2. **CAS authentication not working:**
   - Verify CAS configuration in `.env` file
   - Check that `function_exists('cas')` returns true
   - Ensure CAS hostname is not the default placeholder

3. **Routes not loading correctly:**
   - Clear route cache: `php artisan route:clear`
   - Verify authentication service is accessible

### Debug Mode

Use the debug route to check authentication status:
```
GET /debug-cas
```

This provides detailed information about CAS configuration and authentication status.
