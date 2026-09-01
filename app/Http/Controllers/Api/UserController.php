<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get list of users (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $search = $validated['search'] ?? null;

        $query = User::with('roles');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('net_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    /**
     * Get specific user information.
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['roles.applications']);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'net_id' => $user->net_id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                        'applications' => $role->applications->map(function ($app) {
                            return [
                                'id' => $app->id,
                                'name' => $app->name,
                                'description' => $app->description,
                            ];
                        }),
                    ];
                }),
                'accessible_applications' => $user->roles()
                    ->with('applications')
                    ->get()
                    ->pluck('applications')
                    ->flatten()
                    ->unique('id')
                    ->map(function ($app) {
                        return [
                            'id' => $app->id,
                            'name' => $app->name,
                            'description' => $app->description,
                            'url' => $app->full_url,
                        ];
                    })
                    ->values()
                    ->toArray(),
            ]
        ]);
    }
}
