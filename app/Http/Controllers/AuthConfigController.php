<?php

namespace App\Http\Controllers;

use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class AuthConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'auth.type']);
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || (!$user->hasRole('admin') && !$user->is_admin)) {
                abort(403, 'Access denied. Admin role required.');
            }
            return $next($request);
        });
    }

    /**
     * Show the authentication configuration page
     */
    public function index()
    {
        $currentType = AuthenticationService::getType();
        $casAvailable = AuthenticationService::isCasAvailable();

        return view('admin.auth-config', compact('currentType', 'casAvailable'));
    }

    /**
     * Update authentication configuration
     */
    public function update(Request $request)
    {
        $request->validate([
            'auth_type' => 'required|in:laravel,cas'
        ]);

        $currentType = AuthenticationService::getType();
        $newType = $request->input('auth_type');

        // If no change, redirect back with message
        if ($currentType === $newType) {
            return redirect()
                ->route('auth-config.index')
                ->with('info', 'Authentication type is already set to: ' . AuthenticationService::getTypeDisplayName());
        }

        // Store current user info for re-authentication
        $currentUser = auth()->user();
        $currentUserId = $currentUser ? $currentUser->id : null;
        $currentUserNetId = $currentUser ? $currentUser->net_id : null;

        // Read current .env file
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        // Update or add AUTH_TYPE
        if (preg_match('/^AUTH_TYPE=.*$/m', $envContent)) {
            $envContent = preg_replace('/^AUTH_TYPE=.*$/m', "AUTH_TYPE={$newType}", $envContent);
        } else {
            // Add after APP_TIMEZONE if AUTH_TYPE doesn't exist
            $envContent = preg_replace(
                '/^(APP_TIMEZONE=.*$)/m',
                "$1\n\n# Authentication Configuration\n# Options: 'laravel' (default) or 'cas'\nAUTH_TYPE={$newType}",
                $envContent
            );
        }

        // Write back to .env file
        File::put($envPath, $envContent);

        // Clear config cache to ensure changes take effect
        Artisan::call('config:clear');
        if (app()->routesAreCached()) {
            Artisan::call('route:clear');
        }

        // Handle the authentication transition
        if ($newType === 'cas') {
            // Switching to CAS: log out and send user to login page.
            // The login page exposes the CAS sign-in action without forcing an immediate redirect,
            // which avoids CAS/login redirect loops when CAS is temporarily unavailable.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('success', 'Authentication switched to CAS. Please continue with "Sign In with CAS".');
        } else {
            // Switching to Laravel: Keep user logged in if possible  
            if ($currentUser) {
                // Re-authenticate the user in Laravel auth system
                Auth::login($currentUser);
            }

            return redirect()
                ->route('auth-config.index')
                ->with('success', 'Authentication type updated to: ' . AuthenticationService::getTypeDisplayName());
        }
    }
}