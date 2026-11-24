<?php

namespace Modules\Core\Presentation\Http\Controllers;

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
            if (!auth()->user()->isAdmin()) {
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

        $onlineUsers = \Modules\Core\Models\User::online()->orderByDesc('last_seen_at')->get();

        $stats = [
            'total_users' => \Modules\Core\Models\User::count(),
            'active_users' => \Modules\Core\Models\User::where('is_active', true)->count(),
            'inactive_users' => \Modules\Core\Models\User::where('is_active', false)->count(),
            'online_users' => $onlineUsers->count(),
            'admin_users' => \Modules\Core\Models\User::withRole('admin')->count(),
            'manager_users' => \Modules\Core\Models\User::withRole('manager')->count(),
            'supervisor_users' => \Modules\Core\Models\User::withRole('supervisor')->count(),
            'total_roles' => \Modules\Core\Models\Role::count(),
            'active_roles' => \Modules\Core\Models\Role::where('is_active', true)->count(),
            'total_applications' => \Modules\Core\Models\Application::count(),
            'active_applications' => \Modules\Core\Models\Application::where('is_active', true)->count(),
        ];

        // Get applications user has access to
        $userApplications = \Modules\Core\Models\Application::visibleToUser($user)->get();

        return view('home', compact('stats', 'userApplications', 'onlineUsers'));
    }
}
