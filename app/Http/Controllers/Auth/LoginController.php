<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Core\Application\Auth\AuthenticationManager;
use Modules\Core\Application\Impersonation\ImpersonationManager;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    public function __construct(
        private readonly AuthenticationManager $authenticationManager,
        private readonly ImpersonationManager $impersonationManager,
    )
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Show the application's login form.
     */
    public function showLoginForm()
    {
        // Always render the login page; in CAS mode it presents the CAS sign-in action.
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        $casUsable = $this->authenticationManager->isCasAvailable()
            || $this->authenticationManager->hasValidCasMasqueradeUser();

        // Local email/password login is disabled while CAS authentication is active.
        if ($this->authenticationManager->isCasAuth() && $casUsable) {
            return redirect()->route('login')->with(
                'info',
                'Local login is disabled while CAS authentication is active. Please use "Sign In with CAS".'
            );
        }

        // Validate the form input
        $this->validateLogin($request);

        // Otherwise, use the standard Laravel authentication
        return $this->attemptLogin($request)
            ? $this->sendLoginResponse($request)
            : $this->sendFailedLoginResponse($request);
    }

    /**
     * Get the post-login redirect path.
     */
    protected function redirectTo()
    {
        $user = auth()->user();

        if ($user && ($user->hasRole('admin') || $user->is_admin)) {
            return '/home'; // Admin dashboard
        }

        return '/dashboard'; // User dashboard
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        if ($this->impersonationManager->isImpersonating()) {
            try {
                $this->impersonationManager->stop($request, 'logout');

                return redirect()->route('home')->with(
                    'success',
                    'Impersonation stopped. You are now back in your admin account.'
                );
            } catch (\Throwable $e) {
                Log::error('Laravel logout: failed to stop impersonation', [
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                return redirect()->route('login')->with('error', 'Unable to stop impersonation. Please sign in again.');
            }
        }

        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect('/');
    }
}
