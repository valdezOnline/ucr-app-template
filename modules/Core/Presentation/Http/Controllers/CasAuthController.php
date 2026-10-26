<?php

namespace Modules\Core\Presentation\Http\Controllers;

use Modules\Core\Models\User;
use Modules\Core\Services\CasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Core\Application\Auth\AuthenticationManager;
use Modules\Core\Application\Impersonation\ImpersonationManager;

class CasAuthController extends Controller
{
    public function __construct(
        private readonly AuthenticationManager $authenticationManager,
        private readonly ImpersonationManager $impersonationManager,
    ) {
    }

    public function login(Request $request): RedirectResponse
    {
        if (!$this->authenticationManager->isCasAuth()) {
            return redirect()->route('login')->with('info', 'CAS authentication is not currently enabled.');
        }

        Log::info('CAS Login Route: Starting authentication process', [
            'session_data' => [
                'cas_user' => session('cas_user'),
                'intended_url' => session('url.intended'),
            ],
            'request' => [
                'full_url' => $request->fullUrl(),
                'has_ticket' => $request->has('ticket'),
                'scheme' => $request->getScheme(),
                'host' => $request->getHost(),
            ],
        ]);

        if (config('cas.cas_masquerade')) {
            $netId = config('cas.cas_masquerade');
            Log::info('CAS Login Route: Using masquerade mode', ['net_id' => $netId]);

            $user = User::where('net_id', $netId)->first();
            if ($user && $user->is_active) {
                session(['cas_user' => $netId]);
                Auth::login($user);
                Log::info('CAS Login Route: Masquerade user authenticated', ['net_id' => $netId]);

                if ($user->hasRole('admin') || $user->is_admin) {
                    return redirect()->intended(route('home'));
                }

                return redirect()->intended(route('dashboard'));
            }

            Log::warning('CAS Login Route: Masquerade user not found or inactive; blocking CAS fallback', ['net_id' => $netId]);
            return redirect()->route('login')->with(
                'error',
                'CAS masquerade user does not have access to the system or was not found or is inactive.'
            );
        }

        if (!function_exists('cas')) {
            return redirect()->route('login')->with('error', 'CAS authentication is not available.');
        }

        try {
            Log::info('CAS Login Route: Resolving authenticated user', [
                'has_ticket' => $request->has('ticket'),
            ]);

            $netId = CasService::authenticatedUser($request->has('ticket'));

            Log::info('CAS Login Route: Authenticated user resolution completed', [
                'has_ticket' => $request->has('ticket'),
                'net_id_present' => !empty($netId),
            ]);

            if ($netId) {
                session(['cas_user' => $netId]);
                Log::info('CAS Login Route: User authenticated', [
                    'net_id' => $netId,
                    'source' => $request->has('ticket') ? 'ticket_or_session' : 'session',
                ]);

                $user = User::where('net_id', $netId)->first();
                if ($user && $user->is_active) {
                    Auth::login($user);

                    if ($user->hasRole('admin') || $user->is_admin) {
                        return redirect()->intended(route('home'));
                    }

                    return redirect()->intended(route('dashboard'));
                }

                Log::warning('CAS Login Route: User authenticated via CAS but not authorized locally', ['net_id' => $netId]);
                return redirect()->route('login')->with('error', 'You have successfully authenticated via CAS, but you do not have access to this application.');
            }

            Log::info('CAS Login Route: User not authenticated, forcing CAS authentication');

            $returnService = route('cas.login');
            if ($request->isSecure() && str_starts_with($returnService, 'http://')) {
                $returnService = 'https://' . substr($returnService, 7);
            }

            Log::info('CAS Login Route: Forcing CAS authentication with callback service', [
                'service' => $returnService,
            ]);

            cas()->forceAuthenticationWithReturnService($returnService);

            Log::info('CAS Login Route: Force authentication returned without redirect - checking auth status');

            $netId = CasService::authenticatedUser(true);
            if ($netId) {
                session(['cas_user' => $netId]);
                Log::info('CAS Login Route: Authentication successful after force', [
                    'net_id' => $netId,
                ]);

                $user = User::where('net_id', $netId)->first();
                if ($user && ($user->hasRole('admin') || $user->is_admin)) {
                    return redirect()->intended(route('home'));
                }

                return redirect()->intended(route('dashboard'));
            }

            Log::warning('CAS Login Route: Authentication completed but no user ID returned');
            return redirect()->route('login')->with('error', 'CAS authentication failed to return user information.');
        } catch (\Throwable $e) {
            Log::error('CAS Login Route: Authentication error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'CAS authentication failed. Please try again.');
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($this->impersonationManager->isImpersonating()) {
            try {
                $this->impersonationManager->stop($request, 'logout');

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

        if (!$this->authenticationManager->isCasAuth()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('success', 'You have been logged out successfully.');
        }

        Auth::logout();

        if (session()->has('cas_user')) {
            session()->forget('cas_user');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (function_exists('cas')) {
            cas()->logout();
        }

        return redirect('/')->with('success', 'You have been logged out of the system successfully. To complete the logout process, please close your browser.');
    }
}
