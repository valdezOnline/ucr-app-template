<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ApiClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;

class UserController extends Controller
{
    protected ApiClientService $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->middleware('auth');
        $this->apiClient = $apiClient;

        // Add permission middleware for specific actions
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            $action = $request->route()->getActionMethod();

            // Actions that managers can perform
            $managerAllowedActions = ['assignRole', 'removeRole', 'create', 'store', 'edit', 'update', 'toggleStatus', 'syncWithHr', 'importFromHr'];

            // Actions that require admin access only (delete users)
            $adminOnlyActions = ['destroy'];

            if (in_array($action, $adminOnlyActions)) {
                if (!$user->hasRole('admin')) {
                    abort(403, 'Only administrators can delete users.');
                }
            } elseif (in_array($action, $managerAllowedActions)) {
                if (!$user->hasRole('admin') && !$user->hasRole('manager') && !$user->hasRole('library_manager')) {
                    abort(403, 'You do not have permission to perform this action.');
                }
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with(['roles']);

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('net_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('department', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        // Apply role filter (both legacy and dynamic)
        if ($request->has('role') && !empty($request->role)) {
            if (in_array($request->role, ['admin', 'manager', 'supervisor'])) {
                $query->withRole($request->role);
            } else {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role)->orWhere('display_name', $request->role);
                });
            }
        }

        $users = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        // Get available roles for filtering
        $availableRoles = \App\Models\Role::active()->orderBy('display_name')->get();

        return view('users.index', compact('users', 'availableRoles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = \App\Models\Role::active()->orderBy('display_name')->get();
        $applications = \App\Models\Application::active()
            ->with('roles')
            ->orderBy('name')
            ->get();
        return view('users.create', compact('roles', 'applications'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'net_id' => ['required', 'string', 'max:255', 'unique:users'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'department' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'supervisor_net_id' => ['nullable', 'string', 'max:255'],
            'supervisor_name' => ['nullable', 'string', 'max:255'],
            'is_admin' => ['boolean'],
            'is_manager' => ['boolean'],
            'is_supervisor' => ['boolean'],
            'is_active' => ['boolean'],
            'password' => ['required', Rules\Password::defaults()],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
            'application_roles' => ['array'],
            'application_roles.*' => ['array'],
            'application_roles.*.*' => ['exists:roles,id'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);

        // Prevent managers from assigning admin privileges
        if (!auth()->user()->hasRole('admin') && isset($validated['is_admin']) && $validated['is_admin']) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Only administrators can assign administrator privileges.');
        }

        $user = User::create($validated);

        // Assign dynamic roles
        if (!empty($validated['roles'])) {
            foreach ($validated['roles'] as $roleId) {
                $user->assignRole($roleId, auth()->id());
            }
        }

        // Assign application-specific roles
        if (!empty($validated['application_roles'])) {
            foreach ($validated['application_roles'] as $applicationId => $roleIds) {
                $application = \App\Models\Application::find($applicationId);
                if ($application && !empty($roleIds)) {
                    // Only assign roles that are actually associated with this application
                    $validRoleIds = $application->roles()->whereIn('roles.id', $roleIds)->pluck('roles.id')->toArray();
                    foreach ($validRoleIds as $roleId) {
                        $user->assignRole($roleId, auth()->id());
                    }
                }
            }
        }

        return redirect()->route('users.show', $user)
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['roles', 'roles.applications']);

        // Try to get updated HR information
        $hrInfo = null;
        try {
            $hrInfo = $this->apiClient->getHrPersonInfo($user->net_id);
        } catch (\Exception $e) {
            Log::warning('Failed to fetch HR info for user display', [
                'user_net_id' => $user->net_id,
                'error' => $e->getMessage()
            ]);
        }

        // Get accessible applications
        $accessibleApplications = $user->accessibleApplications()->get();

        // Get available roles for assignment
        $availableRoles = \App\Models\Role::active()
            ->whereNotIn('id', $user->roles->pluck('id'))
            ->orderBy('display_name')
            ->get();

        // Get applications with role assignments for user
        $applications = \App\Models\Application::active()
            ->with([
                'roles' => function ($query) use ($user) {
                    $query->withPivot('assigned_by', 'created_at');
                }
            ])
            ->orderBy('name')
            ->get();

        return view('users.show', compact('user', 'hrInfo', 'accessibleApplications', 'availableRoles', 'applications'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load('roles');
        $roles = \App\Models\Role::active()->orderBy('display_name')->get();
        $applications = \App\Models\Application::active()
            ->with('roles')
            ->orderBy('name')
            ->get();

        // Get current application-role assignments for the user
        $userRoleIds = $user->roles->pluck('id')->toArray();

        return view('users.edit', compact('user', 'roles', 'applications', 'userRoleIds'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'net_id' => ['required', 'string', 'max:255', 'unique:users,net_id,' . $user->id],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'department' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'supervisor_net_id' => ['nullable', 'string', 'max:255'],
            'supervisor_name' => ['nullable', 'string', 'max:255'],
            'is_admin' => ['boolean'],
            'is_manager' => ['boolean'],
            'is_supervisor' => ['boolean'],
            'is_active' => ['boolean'],
            'password' => ['nullable', Rules\Password::defaults()],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
            'application_roles' => ['array'],
            'application_roles.*' => ['array'],
            'application_roles.*.*' => ['exists:roles,id'],
        ]);

        // Only update password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Prevent managers from assigning admin privileges
        if (!auth()->user()->hasRole('admin')) {
            if (isset($validated['is_admin']) && $validated['is_admin'] && !$user->is_admin) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Only administrators can assign administrator privileges.');
            }
            // If user is already admin and manager tries to remove it, preserve admin status
            if ($user->is_admin) {
                $validated['is_admin'] = true;
            }
        }

        // Update name based on first_name and last_name
        $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);

        $user->update($validated);

        // Collect all role IDs to assign (both general and application-specific)
        $allRoleIds = [];

        // Add general dynamic roles
        if (isset($validated['roles'])) {
            $allRoleIds = array_merge($allRoleIds, $validated['roles']);
        }

        // Add application-specific roles
        if (isset($validated['application_roles'])) {
            foreach ($validated['application_roles'] as $applicationId => $roleIds) {
                $application = \App\Models\Application::find($applicationId);
                if ($application && !empty($roleIds)) {
                    // Only include roles that are actually associated with this application
                    $validRoleIds = $application->roles()->whereIn('roles.id', $roleIds)->pluck('roles.id')->toArray();
                    $allRoleIds = array_merge($allRoleIds, $validRoleIds);
                }
            }
        }

        // Remove duplicates and sync all roles
        $allRoleIds = array_unique($allRoleIds);
        $roleData = [];
        foreach ($allRoleIds as $roleId) {
            $roleData[$roleId] = [
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ];
        }

        $user->roles()->sync($roleData);

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deletion of currently authenticated user
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Sync user data with HR API.
     */
    public function syncWithHr(User $user)
    {
        try {
            $hrInfo = $this->apiClient->getHrPersonInfo($user->net_id);

            if (!$hrInfo) {
                return redirect()->back()
                    ->with('error', 'Could not retrieve HR information for this user.');
            }

            // Update user with HR information
            $firstName = $hrInfo['first_name'] ?? $user->first_name;
            $lastName = $hrInfo['last_name'] ?? $user->last_name;

            $user->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => trim($firstName . ' ' . $lastName),
                'email' => $hrInfo['email'] ?? $user->email,
                'department' => $hrInfo['department'] ?? $user->department,
                'title' => $hrInfo['title'] ?? $user->title,
                'supervisor_net_id' => $hrInfo['supervisor_net_id'] ?? $user->supervisor_net_id,
                'supervisor_name' => $hrInfo['supervisor_name'] ?? $user->supervisor_name,
            ]);

            return redirect()->back()
                ->with('success', 'User information synchronized with HR data successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to sync user with HR data', [
                'user_net_id' => $user->net_id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to synchronize with HR data: ' . $e->getMessage());
        }
    }

    /**
     * Import user from HR API by net_id.
     */
    public function importFromHr(Request $request)
    {
        $validated = $request->validate([
            'net_id' => ['required', 'string', 'max:255'],
        ]);

        $netId = $validated['net_id'];

        // Check if user already exists
        if (User::where('net_id', $netId)->exists()) {
            return redirect()->back()
                ->with('error', 'User with this Net ID already exists.');
        }

        try {
            $hrInfo = $this->apiClient->getHrPersonInfo($netId);

            if (!$hrInfo) {
                return redirect()->back()
                    ->with('error', 'Could not find HR information for Net ID: ' . $netId);
            }

            // Create user with HR information
            $firstName = $hrInfo['first_name'] ?? '';
            $lastName = $hrInfo['last_name'] ?? '';
            $fullName = trim($firstName . ' ' . $lastName);

            $user = User::create([
                'net_id' => $netId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $fullName ?: $netId, // Use full name or fallback to net_id
                'email' => $hrInfo['email'] ?? ($netId . '@ucr.edu'),
                'department' => $hrInfo['department'] ?? '',
                'title' => $hrInfo['title'] ?? '',
                'supervisor_net_id' => $hrInfo['supervisor_net_id'] ?? '',
                'supervisor_name' => $hrInfo['supervisor_name'] ?? '',
                'is_active' => true,
                'password' => Hash::make(Str::random(32)), // Generate random password
            ]);

            return redirect()->route('users.show', $user)
                ->with('success', 'User imported from HR data successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to import user from HR data', [
                'net_id' => $netId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to import from HR data: ' . $e->getMessage());
        }
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user)
    {
        // Prevent deactivating currently authenticated user
        if ($user->id === auth()->id() && $user->is_active) {
            return redirect()->back()
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "User {$status} successfully.");
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = \App\Models\Role::findOrFail($validated['role_id']);

        if ($user->roles()->where('role_id', $role->id)->exists()) {
            return redirect()->back()
                ->with('error', 'User already has this role.');
        }

        $user->assignRole($role, auth()->id());

        return redirect()->back()
            ->with('success', "Role '{$role->display_name}' assigned to user.");
    }

    /**
     * Remove a role from a user.
     */
    public function removeRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = \App\Models\Role::findOrFail($validated['role_id']);
        $user->removeRole($role);

        return redirect()->back()
            ->with('success', "Role '{$role->display_name}' removed from user.");
    }
}
