<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the user dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();

        // Get applications user has access to through their roles
        $userApplications = Application::visibleToUser($user)
            ->active()
            ->orderBy('name')
            ->get();

        // Get user profile information
        $profile = [
            'name' => $user->full_name,
            'email' => $user->email,
            'department' => $user->department,
            'title' => $user->title,
            'roles' => $user->getRoles(),
            'supervisor' => $user->supervisor_name,
            'net_id' => $user->net_id,
        ];

        return view('user-dashboard', compact('userApplications', 'profile'));
    }

    /**
     * Show the user's profile (read-only view).
     */
    public function profile()
    {
        $user = auth()->user();

        // Load user with roles for detailed view
        $user->load('roles');

        return view('users.profile', compact('user'));
    }
}