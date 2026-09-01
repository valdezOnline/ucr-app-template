<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->hasRole('admin')) {
                abort(403, 'Only administrators can manage roles.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        $query = Role::with(['creator', 'users', 'applications']);

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('display_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        $roles = $query->orderBy('display_name')->paginate(15)->withQueryString();

        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        return view('roles.create');
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['boolean'],
        ]);

        // Generate unique name from display_name
        $name = Str::slug($validated['display_name'], '_');
        $counter = 1;
        $originalName = $name;

        while (Role::where('name', $name)->exists()) {
            $name = $originalName . '_' . $counter;
            $counter++;
        }

        Role::create([
            'name' => $name,
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
            'color' => $validated['color'],
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->load(['creator', 'users', 'applications']);

        // Get users that are not yet assigned to this role
        $availableUsers = \App\Models\User::active()
            ->whereNotIn('id', $role->users->pluck('id'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('roles.show', compact('role', 'availableUsers'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        return view('roles.edit', compact('role'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['boolean'],
        ]);

        // Ensure is_active is properly handled when checkbox is not checked
        $validated['is_active'] = $request->has('is_active');

        $role->update($validated);

        return redirect()->route('roles.show', $role)
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role)
    {
        // Check if role is assigned to any users or applications
        if ($role->users()->count() > 0 || $role->applications()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete role that is assigned to users or applications.');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Assign role to a user.
     */
    public function assignToUser(Request $request, Role $role)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($user->roles()->where('role_id', $role->id)->exists()) {
            return redirect()->back()
                ->with('error', 'User already has this role.');
        }

        $user->assignRole($role, auth()->id());

        return redirect()->back()
            ->with('success', "Role '{$role->display_name}' assigned to {$user->full_name}.");
    }

    /**
     * Remove role from a user.
     */
    public function removeFromUser(Request $request, Role $role)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->removeRole($role);

        return redirect()->back()
            ->with('success', "Role '{$role->display_name}' removed from {$user->full_name}.");
    }

    /**
     * Toggle role active status.
     */
    public function toggleStatus(Role $role)
    {
        $role->update(['is_active' => !$role->is_active]);

        $status = $role->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Role {$status} successfully.");
    }
}
