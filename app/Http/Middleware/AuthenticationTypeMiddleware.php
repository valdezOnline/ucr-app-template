<?php

namespace App\Http\Middleware;

use App\Services\AuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuthenticationTypeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $authType = config('auth.type', 'laravel');

        // Skip middleware for auth-config routes when user is already authenticated
        // This prevents logout during auth type switching
        if ($request->routeIs('auth-config.*') && Auth::check()) {
            return $next($request);
        }

        // If Laravel authentication is enabled and user is already authenticated, continue
        if ($authType === 'laravel' && Auth::check()) {
            return $next($request);
        }

        // If CAS authentication is enabled, handle CAS authentication
        if ($authType === 'cas') {
            return $this->handleCasAuthentication($request, $next);
        }

        // If no authentication or authentication failed, redirect to login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        return $next($request);
    }

    /**
     * Handle CAS authentication logic
     */
    protected function handleCasAuthentication(Request $request, Closure $next)
    {
        try {
            // Handle masquerade mode first
            if (config('cas.cas_masquerade')) {
                $masqueradeUser = config('cas.cas_masquerade');
                if ($masqueradeUser && !Auth::check()) {
                    $user = User::where('net_id', $masqueradeUser)->first();
                    if ($user && $user->is_active) {
                        Auth::login($user);
                        Log::info("CAS masquerade user authenticated", ['net_id' => $masqueradeUser]);
                        return $next($request);
                    }

                    // Ignore invalid masquerade users and continue with normal CAS authentication flow.
                    Log::warning("CAS masquerade user not found or inactive; falling back to normal CAS flow", ['net_id' => $masqueradeUser]);
                    config(['cas.cas_masquerade' => null]);
                    session()->forget('cas_user');
                }
            }

            // Check if already authenticated via CAS (non-masquerade)
            if (function_exists('cas') && cas()->isAuthenticatedFromSession()) {
                $netId = cas()->user();

                if ($netId && !Auth::check()) {
                    // Find user based on CAS authentication
                    $user = User::where('net_id', $netId)->first();

                    if ($user && $user->is_active) {
                        Auth::login($user);
                        Log::info("CAS user authenticated via middleware", ['net_id' => $netId]);
                    } else {
                        Log::warning("CAS user not found or inactive", ['net_id' => $netId]);

                        if (AuthenticationService::isCasAuth()) {
                            return redirect()->route(AuthenticationService::getLoginRouteName())->with('error', 'User account not found or inactive.');
                        }

                        return redirect()->route('login')->with('error', 'User account not found or inactive.');
                    }
                }
            }

            // If user is authenticated, continue
            if (Auth::check()) {
                return $next($request);
            }

            // Skip redirect for login/logout related routes to prevent infinite loops
            if (
                $request->routeIs('cas.login', 'cas.logout', 'login', 'logout') ||
                $request->is('/') ||
                $request->is('help')
            ) {
                return $next($request);
            }

            // For protected routes, redirect to appropriate login
            return redirect()->route(AuthenticationService::getLoginRouteName());

        } catch (\Exception $e) {
            Log::error('CAS authentication middleware error: ' . $e->getMessage());
            return $next($request);
        }
    }

    /**
     * Check if CAS is available and configured
     */
    protected function isCasAvailable(): bool
    {
        return function_exists('cas') &&
            config('cas.cas_hostname') &&
            config('cas.cas_hostname') !== 'cas.myuniv.edu';
    }
}