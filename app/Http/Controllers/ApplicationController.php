<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            $action = $request->route()->getActionMethod();

            // Allow managers to view and assign roles/users, but restrict create/update/delete to admins
            $managerAllowedActions = ['index', 'show', 'assignRole', 'removeRole', 'assignUser', 'removeUser'];

            if (in_array($action, $managerAllowedActions)) {
                if (!$user->hasRole('admin') && !$user->hasRole('manager') && !$user->hasRole('library_manager')) {
                    abort(403, 'You do not have permission to access this resource.');
                }
            } else {
                if (!$user->hasRole('admin')) {
                    abort(403, 'Only administrators can create, update, or delete applications.');
                }
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of applications.
     */
    public function index(Request $request)
    {
        $query = Application::with(['creator', 'roles'])
            ->withCount('roles as roles_count');

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('url', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        $applications = $query->orderBy('name')->paginate(15)->withQueryString();

        // Add users with access count for each application
        $applications->getCollection()->transform(function ($application) {
            if ($application->roles->isNotEmpty()) {
                $roleIds = $application->roles->pluck('id');
                $application->users_with_access_count = \App\Models\User::whereHas('roles', function ($query) use ($roleIds) {
                    $query->whereIn('roles.id', $roleIds);
                })->count();
            } else {
                $application->users_with_access_count = 0;
            }
            return $application;
        });

        return view('applications.index', compact('applications'));
    }

    /**
     * Show the form for creating a new application.
     */
    public function create()
    {
        $roles = Role::active()->orderBy('display_name')->get();
        return view('applications.create', compact('roles'));
    }

    /**
     * Store a newly created application in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'url' => ['nullable', 'url', 'max:255'],
            'url_type' => ['required', 'in:external,sub-application,folder'],
            'sub_path' => ['nullable', 'string', 'max:500'],
            'base_domain' => ['nullable', 'url', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
            // API Authentication fields
            'supports_api_auth' => ['boolean'],
            'generate_credentials' => ['boolean'],
            'api_key_expires_at' => ['nullable', 'date', 'after:today'],
            'allowed_domains' => ['nullable', 'string'],
        ]);

        // Additional validation based on URL type
        if ($validated['url_type'] === 'external') {
            $request->validate([
                'url' => ['required', 'url', 'max:255'],
            ]);
        } elseif (in_array($validated['url_type'], ['sub-application', 'folder'])) {
            $request->validate([
                'base_domain' => ['required', 'url', 'max:255'],
                'sub_path' => ['required', 'string', 'max:500'],
            ]);
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('application-logos', 'public');
        }

        // Process allowed domains
        $allowedDomains = null;
        if (!empty($validated['allowed_domains'])) {
            $allowedDomains = array_filter(
                array_map('trim', explode("\n", $validated['allowed_domains'])),
                fn($domain) => !empty($domain)
            );
        }

        $application = Application::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'url' => $validated['url'],
            'url_type' => $validated['url_type'],
            'sub_path' => $validated['sub_path'],
            'base_domain' => $validated['base_domain'],
            'icon' => $validated['icon'] ?? 'fas fa-cube',
            'logo' => $validated['logo'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
            // API Authentication fields
            'supports_api_auth' => $validated['supports_api_auth'] ?? false,
            'allowed_domains' => $allowedDomains,
            'api_key_expires_at' => $validated['api_key_expires_at'] ?? null,
        ]);

        // Generate API credentials if requested
        $apiCredentials = null;
        if (($validated['supports_api_auth'] ?? false) && ($validated['generate_credentials'] ?? false)) {
            $apiCredentials = $application->generateApiCredentials();
        }

        // Assign roles to application
        if (!empty($validated['roles'])) {
            $roleData = [];
            foreach ($validated['roles'] as $roleId) {
                $roleData[$roleId] = ['assigned_by' => auth()->id()];
            }
            $application->roles()->attach($roleData);
        }

        $successMessage = 'Application created successfully.';

        // Add API credentials to session if generated
        if ($apiCredentials) {
            session()->flash('api_credentials', $apiCredentials);
            $successMessage .= ' API credentials have been generated and are displayed below.';
        }

        return redirect()->route('applications.show', $application)
            ->with('success', $successMessage);
    }

    /**
     * Display the specified application.
     */
    public function show(Application $application)
    {
        $application->load(['creator', 'roles.users']);

        // Get users who have access to this application through their roles
        $usersWithAccess = 0;
        $usersWithAccessDetailed = collect();

        if ($application->roles->isNotEmpty()) {
            $roleIds = $application->roles->pluck('id');
            $usersWithAccessDetailed = \App\Models\User::whereHas('roles', function ($query) use ($roleIds) {
                $query->whereIn('roles.id', $roleIds);
            })->with([
                        'roles' => function ($query) use ($roleIds) {
                            $query->whereIn('roles.id', $roleIds);
                        }
                    ])->get();

            // Add applicable roles to each user for display
            $usersWithAccessDetailed->each(function ($user) use ($roleIds) {
                $user->applicableRoles = $user->roles->whereIn('id', $roleIds);
            });

            $usersWithAccess = $usersWithAccessDetailed->count();
        }

        // Get roles that are not yet assigned to this application
        $availableRoles = Role::active()
            ->whereNotIn('id', $application->roles->pluck('id'))
            ->orderBy('display_name')
            ->get();

        return view('applications.show', compact('application', 'usersWithAccess', 'usersWithAccessDetailed', 'availableRoles'));
    }

    /**
     * Show the form for editing the specified application.
     */
    public function edit(Application $application)
    {
        $roles = Role::active()->orderBy('display_name')->get();
        $application->load('roles');

        return view('applications.edit', compact('application', 'roles'));
    }

    /**
     * Update the specified application in storage.
     */
    public function update(Request $request, Application $application)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'url' => ['nullable', 'url', 'max:255'],
            'url_type' => ['required', 'in:external,sub-application,folder'],
            'sub_path' => ['nullable', 'string', 'max:500'],
            'base_domain' => ['nullable', 'url', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
            // API Authentication fields
            'supports_api_auth' => ['boolean'],
            'generate_credentials' => ['boolean'],
            'api_key_expires_at' => ['nullable', 'date', 'after:today'],
            'allowed_domains' => ['nullable', 'string'],
            'api_active' => ['boolean'],
        ]);

        // Additional validation based on URL type
        if ($validated['url_type'] === 'external') {
            $request->validate([
                'url' => ['required', 'url', 'max:255'],
            ]);
        } elseif (in_array($validated['url_type'], ['sub-application', 'folder'])) {
            $request->validate([
                'base_domain' => ['required', 'url', 'max:255'],
                'sub_path' => ['required', 'string', 'max:500'],
            ]);
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if it exists
            if ($application->logo) {
                Storage::disk('public')->delete($application->logo);
            }
            $validated['logo'] = $request->file('logo')->store('application-logos', 'public');
        }

        // Process allowed domains
        $allowedDomains = null;
        if (!empty($validated['allowed_domains'])) {
            $allowedDomains = array_filter(
                array_map('trim', explode("\n", $validated['allowed_domains'])),
                fn($domain) => !empty($domain)
            );
        }

        // Handle API authentication updates
        $apiCredentials = null;
        $apiUpdates = [];

        if ($validated['supports_api_auth'] ?? false) {
            $apiUpdates['supports_api_auth'] = true;
            $apiUpdates['allowed_domains'] = $allowedDomains;

            // Generate new credentials if requested and no existing credentials
            if (($validated['generate_credentials'] ?? false) && !$application->api_key) {
                // Will be generated after the main update
            } elseif ($application->api_key) {
                // Update existing API settings
                $apiUpdates['api_active'] = $validated['api_active'] ?? $application->api_active;
            }
        } else {
            // Disable API auth but don't remove credentials
            $apiUpdates['supports_api_auth'] = false;
            $apiUpdates['api_active'] = false;
        }

        $application->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'url' => $validated['url'],
            'url_type' => $validated['url_type'],
            'sub_path' => $validated['sub_path'],
            'base_domain' => $validated['base_domain'],
            'icon' => $validated['icon'] ?? $application->icon,
            'logo' => $validated['logo'] ?? $application->logo,
            'is_active' => $validated['is_active'] ?? $application->is_active,
        ] + $apiUpdates);

        // Generate API credentials if requested for new API setup
        if (
            ($validated['supports_api_auth'] ?? false) &&
            ($validated['generate_credentials'] ?? false) &&
            !$application->api_key
        ) {

            $apiCredentials = $application->generateApiCredentials();

            // Update expiration if provided
            if ($validated['api_key_expires_at'] ?? null) {
                $application->update(['api_key_expires_at' => $validated['api_key_expires_at']]);
            }
        }

        // Update roles assignment
        if (isset($validated['roles'])) {
            $roleData = [];
            foreach ($validated['roles'] as $roleId) {
                $roleData[$roleId] = ['assigned_by' => auth()->id()];
            }
            $application->roles()->sync($roleData);
        } else {
            $application->roles()->detach();
        }

        $successMessage = 'Application updated successfully.';

        // Add API credentials to session if generated
        if ($apiCredentials) {
            session()->flash('api_credentials', $apiCredentials);
            $successMessage .= ' New API credentials have been generated and are displayed below.';
        }

        return redirect()->route('applications.show', $application)
            ->with('success', $successMessage);
    }

    /**
     * Remove the specified application from storage.
     */
    public function destroy(Application $application)
    {
        // Delete logo if it exists
        if ($application->logo) {
            Storage::disk('public')->delete($application->logo);
        }

        $application->delete();

        return redirect()->route('applications.index')
            ->with('success', 'Application deleted successfully.');
    }

    /**
     * Toggle application active status.
     */
    public function toggleStatus(Application $application)
    {
        $application->update(['is_active' => !$application->is_active]);

        $status = $application->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Application {$status} successfully.");
    }

    /**
     * Assign role to application.
     */
    public function assignRole(Request $request, Application $application)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        if ($application->roles()->where('role_id', $role->id)->exists()) {
            return redirect()->back()
                ->with('error', 'Role is already assigned to this application.');
        }

        $application->roles()->attach($role->id, ['assigned_by' => auth()->id()]);

        return redirect()->back()
            ->with('success', "Role '{$role->display_name}' assigned to application.");
    }

    /**
     * Remove role from application.
     */
    public function removeRole(Request $request, Application $application)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $application->roles()->detach($role->id);

        return redirect()->back()
            ->with('success', "Role '{$role->display_name}' removed from application.");
    }

    /**
     * Revoke API access for an application.
     */
    public function revokeApiAccess(Application $application)
    {
        $application->revokeApiAccess();

        return redirect()->back()
            ->with('success', 'API access has been revoked successfully.');
    }

    /**
     * Regenerate API credentials for an application.
     */
    public function regenerateApiCredentials(Application $application)
    {
        if (!$application->supports_api_auth) {
            return redirect()->back()
                ->with('error', 'API authentication is not enabled for this application.');
        }

        $apiCredentials = $application->generateApiCredentials();

        session()->flash('api_credentials', $apiCredentials);

        return redirect()->back()
            ->with('success', 'New API credentials have been generated. Please update your external applications with the new credentials.');
    }

    /**
     * Assign user to application by giving them appropriate roles.
     */
    public function assignUser(Request $request, Application $application)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $role = Role::findOrFail($validated['role_id']);

        // Check if the role is assigned to this application
        if (!$application->roles()->where('role_id', $role->id)->exists()) {
            return redirect()->back()
                ->with('error', "Role '{$role->display_name}' is not assigned to this application.");
        }

        // Check if user already has this role
        if ($user->roles()->where('role_id', $role->id)->exists()) {
            return redirect()->back()
                ->with('error', "User already has the '{$role->display_name}' role.");
        }

        // Assign the role to the user
        $user->assignRole($role, auth()->id());

        return redirect()->back()
            ->with('success', "User '{$user->full_name}' assigned to application with role '{$role->display_name}'.");
    }

    /**
     * Remove user from application by removing their roles.
     */
    public function removeUser(Request $request, Application $application)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $role = Role::findOrFail($validated['role_id']);

        // Remove the role from the user
        $user->removeRole($role);

        return redirect()->back()
            ->with('success', "User '{$user->full_name}' removed from application role '{$role->display_name}'.");
    }
}