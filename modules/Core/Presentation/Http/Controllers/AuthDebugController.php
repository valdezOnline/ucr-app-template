<?php

namespace Modules\Core\Presentation\Http\Controllers;

use Modules\Core\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Application\Auth\AuthenticationManager;

class AuthDebugController extends Controller
{
    public function __construct(
        private readonly AuthenticationManager $authenticationManager,
    ) {
    }

    public function testAuth(Request $request): JsonResponse
    {
        $this->ensureLocalEnvironment();

        return response()->json([
            'auth_type' => $this->authenticationManager->getType(),
            'cas_masquerade' => config('cas.cas_masquerade'),
            'laravel_auth_check' => auth()->check(),
            'laravel_user' => auth()->user() ? auth()->user()->only(['id', 'net_id', 'is_admin']) : null,
            'session_cas_user' => session('cas_user'),
            'routes_available' => [
                'cas_login' => \Illuminate\Support\Facades\Route::has('cas.login'),
                'cas_logout' => \Illuminate\Support\Facades\Route::has('cas.logout'),
                'login' => \Illuminate\Support\Facades\Route::has('login'),
            ],
        ]);
    }

    public function switchToCas(): RedirectResponse
    {
        $this->ensureLocalEnvironment();

        $this->authenticationManager->switchAuthenticationType('cas');

        return redirect('/test-auth')->with('success', 'Switched to CAS auth');
    }

    public function switchToLaravel(): RedirectResponse
    {
        $this->ensureLocalEnvironment();

        $this->authenticationManager->switchAuthenticationType('laravel');

        return redirect('/test-auth')->with('success', 'Switched to Laravel auth');
    }

    public function debugCas(): JsonResponse
    {
        $this->ensureLocalEnvironment();

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

                    $user = User::where('net_id', cas()->user())->first();
                    if ($user) {
                        $debug['step'] = 4;
                        $debug['db_user_found'] = true;
                        $debug['user_active'] = $user->is_active;
                        $debug['user_roles'] = $user->getRoles();

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
    }

    private function ensureLocalEnvironment(): void
    {
        abort_unless(app()->environment('local'), 404);
    }
}
