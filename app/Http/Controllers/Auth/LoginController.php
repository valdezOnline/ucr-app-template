<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

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
        // Local email/password login is disabled while CAS authentication is active.
        if (AuthenticationService::isCasAuth()) {
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
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
