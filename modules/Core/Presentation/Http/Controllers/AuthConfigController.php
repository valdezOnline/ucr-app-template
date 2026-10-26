<?php

namespace Modules\Core\Presentation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Application\Auth\AuthenticationManager;

class AuthConfigController extends Controller
{
    public function __construct(
        private readonly AuthenticationManager $authenticationManager,
    )
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
        $currentType = $this->authenticationManager->getType();
        $casAvailable = $this->authenticationManager->isCasAvailable();
        $currentTypeDisplayName = $this->authenticationManager->getTypeDisplayName();
        $registrationEnabled = $this->authenticationManager->registrationEnabled();
        $passwordResetEnabled = $this->authenticationManager->passwordResetEnabled();

        return view('admin.auth-config', compact(
            'currentType',
            'casAvailable',
            'currentTypeDisplayName',
            'registrationEnabled',
            'passwordResetEnabled'
        ));
    }

    /**
     * Update authentication configuration
     */
    public function update(Request $request)
    {
        $request->validate([
            'auth_type' => 'required|in:laravel,cas'
        ]);

        $currentType = $this->authenticationManager->getType();
        $newType = $request->input('auth_type');

        // If no change, redirect back with message
        if ($currentType === $newType) {
            return redirect()
                ->route('auth-config.index')
                ->with('info', 'Authentication type is already set to: ' . $this->authenticationManager->getTypeDisplayName());
        }

        // Store current user info for re-authentication
        $currentUser = auth()->user();
        $currentUserId = $currentUser ? $currentUser->id : null;
        $currentUserNetId = $currentUser ? $currentUser->net_id : null;

        $this->authenticationManager->switchAuthenticationType($newType);

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
                ->with('success', 'Authentication type updated to: ' . $this->authenticationManager->getTypeDisplayName());
        }
    }
}