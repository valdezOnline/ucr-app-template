<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasRole('admin') && !auth()->user()->is_admin) {
                abort(403, 'Access denied. This admin dashboard is restricted to administrators only.');
            }
            return $next($request);
        });
    }

    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();

        $stats = [
            'total_users' => \App\Models\User::count(),
            'active_users' => \App\Models\User::where('is_active', true)->count(),
            'inactive_users' => \App\Models\User::where('is_active', false)->count(),
            'admin_users' => \App\Models\User::where('is_admin', true)->count(),
            'manager_users' => \App\Models\User::where('is_manager', true)->count(),
            'supervisor_users' => \App\Models\User::where('is_supervisor', true)->count(),
            'total_roles' => \App\Models\Role::count(),
            'active_roles' => \App\Models\Role::where('is_active', true)->count(),
            'total_applications' => \App\Models\Application::count(),
            'active_applications' => \App\Models\Application::where('is_active', true)->count(),
        ];

        // Get applications user has access to
        $userApplications = \App\Models\Application::visibleToUser($user)->get();

        return view('home', compact('stats', 'userApplications'));
    }
}
