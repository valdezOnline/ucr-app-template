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

                    // In masquerade mode we should never call real CAS endpoints.
                    Log::warning("CAS masquerade user not found or inactive; blocking CAS fallback", ['net_id' => $masqueradeUser]);
                    return redirect()->route('login')->with(
                        'error',
                        'CAS masquerade user does not have access to the system or was not found or is inactive.'
                    );
                }
            }

            // Resolve CAS user from local phpCAS session, or from a returned ticket if present.
            if (function_exists('cas')) {
                $netId = \App\Services\CasService::authenticatedUser($request->has('ticket'));

                if ($netId !== null) {
                    session(['cas_user' => $netId]);

                    if (!Auth::check()) {
                        // Find user based on CAS authentication
                        $user = User::where('net_id', $netId)->first();

                        if ($user && $user->is_active) {
                            Auth::login($user);
                            Log::info("CAS user authenticated via middleware", [
                                'net_id' => $netId,
                                'has_ticket' => $request->has('ticket'),
                            ]);
                        } else {
                            Log::warning("CAS user authenticated but not authorized locally", ['net_id' => $netId]);

                            if (AuthenticationService::isCasAuth()) {
                                return redirect()->route(AuthenticationService::getLoginRouteName())->with('error', 'You have successfully authenticated via CAS, but you do not have access to this application.');
                            }

                            return redirect()->route('login')->with('error', 'You have successfully authenticated via CAS, but you do not have access to this application.');
                        }
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

        } catch (\Throwable $e) {
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