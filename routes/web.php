<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AdminImpersonationController;
use App\Http\Controllers\SiteStatusController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/help', function () {
    return view('help');
})->name('help');
Route::get('/site-status', [SiteStatusController::class, 'show'])->name('site-status.show');

Route::get('/test-auth', function () {
    return response()->json([
        'auth_type' => config('auth.type'),
        'cas_masquerade' => config('cas.cas_masquerade'),
        'laravel_auth_check' => auth()->check(),
        'laravel_user' => auth()->user() ? auth()->user()->only(['id', 'net_id', 'is_admin']) : null,
        'session_cas_user' => session('cas_user'),
        'routes_available' => [
            'cas_login' => \Illuminate\Support\Facades\Route::has('cas.login'),
            'cas_logout' => \Illuminate\Support\Facades\Route::has('cas.logout'),
            'login' => \Illuminate\Support\Facades\Route::has('login'),
        ]
    ]);
})->name('test-auth');

// Debug routes for testing auth switching (remove in production)
Route::get('/debug/switch-to-cas', function () {
    file_put_contents(base_path('.env'), str_replace('AUTH_TYPE=laravel', 'AUTH_TYPE=cas', file_get_contents(base_path('.env'))));
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    return redirect('/test-auth')->with('success', 'Switched to CAS auth');
})->name('debug.switch-cas');

Route::get('/debug/switch-to-laravel', function () {
    file_put_contents(base_path('.env'), str_replace('AUTH_TYPE=cas', 'AUTH_TYPE=laravel', file_get_contents(base_path('.env'))));
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    return redirect('/test-auth')->with('success', 'Switched to Laravel auth');
})->name('debug.switch-laravel');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home')->middleware(['auth', 'auth.type']);
Route::get('/dashboard', [App\Http\Controllers\UserDashboardController::class, 'index'])->name('dashboard')->middleware(['auth', 'auth.type']);
Route::post('/dashboard/applications/order', [App\Http\Controllers\UserDashboardController::class, 'saveApplicationOrder'])->name('dashboard.applications.order')->middleware(['auth', 'auth.type']);
Route::post('/dashboard/shortcuts', [App\Http\Controllers\UserDashboardController::class, 'storeShortcut'])->name('dashboard.shortcuts.store')->middleware(['auth', 'auth.type']);
Route::put('/dashboard/shortcuts/{shortcutId}', [App\Http\Controllers\UserDashboardController::class, 'updateShortcut'])->name('dashboard.shortcuts.update')->middleware(['auth', 'auth.type']);
Route::delete('/dashboard/shortcuts/{shortcutId}', [App\Http\Controllers\UserDashboardController::class, 'destroyShortcut'])->name('dashboard.shortcuts.destroy')->middleware(['auth', 'auth.type']);
Route::get('/profile', [App\Http\Controllers\UserDashboardController::class, 'profile'])->name('profile')->middleware(['auth', 'auth.type']);

// User Management Routes
Route::middleware(['auth', 'auth.type'])->group(function () {
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::patch('users/{user}/sync-with-hr', [App\Http\Controllers\UserController::class, 'syncWithHr'])->name('users.sync-with-hr');
    Route::post('users/import-from-hr', [App\Http\Controllers\UserController::class, 'importFromHr'])->name('users.import-from-hr');
    Route::patch('users/{user}/toggle-status', [App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/assign-role', [App\Http\Controllers\UserController::class, 'assignRole'])->name('users.assign-role');
    Route::delete('users/{user}/remove-role', [App\Http\Controllers\UserController::class, 'removeRole'])->name('users.remove-role');

    // Role Management Routes
    Route::resource('roles', App\Http\Controllers\RoleController::class);
    Route::patch('roles/{role}/toggle-status', [App\Http\Controllers\RoleController::class, 'toggleStatus'])->name('roles.toggle-status');
    Route::post('roles/{role}/assign-user', [App\Http\Controllers\RoleController::class, 'assignToUser'])->name('roles.assign-user');
    Route::delete('roles/{role}/remove-user', [App\Http\Controllers\RoleController::class, 'removeFromUser'])->name('roles.remove-user');

    // Application Management Routes
    Route::resource('applications', App\Http\Controllers\ApplicationController::class);
    Route::patch('applications/{application}/toggle-status', [App\Http\Controllers\ApplicationController::class, 'toggleStatus'])->name('applications.toggle-status');
    Route::post('applications/{application}/assign-role', [App\Http\Controllers\ApplicationController::class, 'assignRole'])->name('applications.assign-role');
    Route::delete('applications/{application}/remove-role', [App\Http\Controllers\ApplicationController::class, 'removeRole'])->name('applications.remove-role');
    Route::post('applications/{application}/assign-user', [App\Http\Controllers\ApplicationController::class, 'assignUser'])->name('applications.assign-user');
    Route::delete('applications/{application}/remove-user', [App\Http\Controllers\ApplicationController::class, 'removeUser'])->name('applications.remove-user');
    // API Management Routes
    Route::delete('applications/{application}/revoke-api', [App\Http\Controllers\ApplicationController::class, 'revokeApiAccess'])->name('applications.revoke-api');
    Route::post('applications/{application}/regenerate-api', [App\Http\Controllers\ApplicationController::class, 'regenerateApiCredentials'])->name('applications.regenerate-api');

    // Authentication Configuration Routes (Admin only)
    Route::get('admin/auth-config', [App\Http\Controllers\AuthConfigController::class, 'index'])->name('auth-config.index');
    Route::put('admin/auth-config', [App\Http\Controllers\AuthConfigController::class, 'update'])->name('auth-config.update');
    Route::get('admin/site-status', [SiteStatusController::class, 'edit'])->name('site-status-config.edit');
    Route::put('admin/site-status', [SiteStatusController::class, 'update'])->name('site-status-config.update');

    // Admin impersonation routes
    Route::post('admin/impersonation/stop', [AdminImpersonationController::class, 'stop'])->name('admin.impersonation.stop');
    Route::post('admin/impersonation/{user}', [AdminImpersonationController::class, 'start'])->name('admin.impersonation.start');
});

// Debug route to test CAS authentication flow
Route::get('/debug-cas', function () {
    $debug = [
        'step' => 1,
        'message' => 'Starting CAS debug',
        'laravel_auth' => auth()->check(),
        'session_cas_user' => session('cas_user'),
    ];

    if (function_exists('cas')) {
        try {
            $debug['step'] = 2;
            $debug['cas_check_result'] = cas()->checkAuthentication();

            if (cas()->checkAuthentication()) {
                $debug['step'] = 3;
                $debug['cas_user'] = cas()->user();

                $user = App\Models\User::where('net_id', cas()->user())->first();
                if ($user) {
                    $debug['step'] = 4;
                    $debug['db_user_found'] = true;
                    $debug['user_active'] = $user->is_active;
                    $debug['user_roles'] = $user->getRoles();

                    // Try to log in the user
                    Auth::login($user);
                    $debug['step'] = 5;
                    $debug['login_successful'] = auth()->check();
                } else {
                    $debug['db_user_found'] = false;
                }
            }
        } catch (\Exception $e) {
            $debug['cas_error'] = $e->getMessage();
        }
    } else {
        $debug['cas_available'] = false;
    }

    return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
})->name('debug-cas');

// CAS Authentication routes (always available to prevent route errors)
// Routes will redirect appropriately based on current auth configuration
Route::get('/cas/login', function () {
    // If CAS authentication is not enabled, redirect to regular login
    if (!\App\Services\AuthenticationService::isCasAuth()) {
        return redirect()->route('login')->with('info', 'CAS authentication is not currently enabled.');
    }

    Log::info("CAS Login Route: Starting authentication process", [
        'session_data' => [
            'cas_user' => session('cas_user'),
            'intended_url' => session('url.intended')
        ],
        'request' => [
            'full_url' => request()->fullUrl(),
            'has_ticket' => request()->has('ticket'),
            'scheme' => request()->getScheme(),
            'host' => request()->getHost(),
        ],
    ]);

    // Handle masquerade mode first
    if (config('cas.cas_masquerade')) {
        $netId = config('cas.cas_masquerade');
        Log::info("CAS Login Route: Using masquerade mode", ['net_id' => $netId]);

        // Find and authenticate the masquerade user
        $user = App\Models\User::where('net_id', $netId)->first();
        if ($user && $user->is_active) {
            session(['cas_user' => $netId]);
            Auth::login($user);
            Log::info("CAS Login Route: Masquerade user authenticated", ['net_id' => $netId]);

            if ($user->hasRole('admin') || $user->is_admin) {
                return redirect()->intended(route('home')); // Admin dashboard
            }
            return redirect()->intended(route('dashboard')); // User dashboard
        }

        Log::warning("CAS Login Route: Masquerade user not found or inactive; blocking CAS fallback", ['net_id' => $netId]);
        return redirect()->route('login')->with(
            'error',
            'CAS masquerade user does not have access to the system or was not found or is inactive.'
        );
    }

    // Check if CAS is available for non-masquerade mode
    if (!function_exists('cas')) {
        return redirect()->route('login')->with('error', 'CAS authentication is not available.');
    }

    try {
        Log::info('CAS Login Route: Resolving authenticated user', [
            'has_ticket' => request()->has('ticket'),
        ]);

        // Resolve user first from local phpCAS session; if ticket exists, validate with CAS server.
        $netId = \App\Services\CasService::authenticatedUser(request()->has('ticket'));

        Log::info('CAS Login Route: Authenticated user resolution completed', [
            'has_ticket' => request()->has('ticket'),
            'net_id_present' => !empty($netId),
        ]);

        if ($netId) {
            session(['cas_user' => $netId]);
            Log::info("CAS Login Route: User authenticated", [
                'net_id' => $netId,
                'source' => request()->has('ticket') ? 'ticket_or_session' : 'session',
            ]);

            // Find and authenticate the user
            $user = App\Models\User::where('net_id', $netId)->first();
            if ($user && $user->is_active) {
                Auth::login($user);

                if ($user->hasRole('admin') || $user->is_admin) {
                    return redirect()->intended(route('home')); // Admin dashboard
                }
                return redirect()->intended(route('dashboard')); // User dashboard
            }

            Log::warning("CAS Login Route: User authenticated via CAS but not authorized locally", ['net_id' => $netId]);
            return redirect()->route('login')->with('error', 'You have successfully authenticated via CAS, but you do not have access to this application.');
        }

        Log::info("CAS Login Route: User not authenticated, forcing CAS authentication");

        // Always return to this route after CAS so we can complete Laravel auth/session setup.
        $returnService = route('cas.login');
        if (request()->isSecure() && str_starts_with($returnService, 'http://')) {
            $returnService = 'https://' . substr($returnService, 7);
        }
        Log::info("CAS Login Route: Forcing CAS authentication with callback service", [
            'service' => $returnService,
        ]);

        // Force CAS authentication - this will redirect to CAS server
        cas()->forceAuthenticationWithReturnService($returnService);

        // This should not be reached if CAS redirects properly
        Log::info("CAS Login Route: Force authentication returned without redirect - checking auth status");

        // Double-check authentication after force (defensive fallback)
        $netId = \App\Services\CasService::authenticatedUser(true);
        if ($netId) {
            session(['cas_user' => $netId]);
            Log::info("CAS Login Route: Authentication successful after force", [
                'net_id' => $netId
            ]);

            // Route to appropriate dashboard based on user role
            $user = App\Models\User::where('net_id', $netId)->first();
            if ($user && ($user->hasRole('admin') || $user->is_admin)) {
                return redirect()->intended(route('home')); // Admin dashboard
            }

            return redirect()->intended(route('dashboard')); // User dashboard
        }

        // If we reach here, authentication failed
        Log::warning("CAS Login Route: Authentication completed but no user ID returned");
        return redirect()->route('login')->with('error', 'CAS authentication failed to return user information.');

    } catch (\Throwable $e) {
        Log::error('CAS Login Route: Authentication error: ' . $e->getMessage());
        return redirect()->route('login')->with('error', 'CAS authentication failed. Please try again.');
    }
})->name('cas.login');

Route::get('/cas/logout', function () {
    if (\App\Services\ImpersonationService::isImpersonating()) {
        try {
            \App\Services\ImpersonationService::stop(request(), 'logout');

            return redirect()->route('home')->with(
                'success',
                'Impersonation stopped. You are now back in your admin account.'
            );
        } catch (\Throwable $e) {
            Log::error('CAS logout: failed to stop impersonation', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('login')->with('error', 'Unable to stop impersonation. Please sign in again.');
        }
    }

    // If CAS authentication is not enabled, use regular logout
    if (!\App\Services\AuthenticationService::isCasAuth()) {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/')->with('success', 'You have been logged out successfully.');
    }

    // Clear Laravel session and auth
    Auth::logout();

    // Clear CAS session if available
    if (session()->has('cas_user')) {
        session()->forget('cas_user');
    }

    // Invalidate session
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    // Perform CAS logout
    if (function_exists('cas')) {
        cas()->logout();
    }

    return Redirect('/')->with('success', 'You have been logged out of the system successfully. To complete the logout process, please close your browser.');
})->name('cas.logout');
