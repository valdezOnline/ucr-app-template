<?php

namespace Modules\Core\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Models\User;

class UserActivityController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();

        abort_unless($user && ($user->hasRole('admin') || $user->is_admin), 403, 'Access denied.');

        return response()->json([
            'online_users' => User::online()->orderByDesc('last_seen_at')->get()->map(function (User $activeUser) {
                return [
                    'id' => $activeUser->id,
                    'name' => $activeUser->full_name ?: $activeUser->name,
                    'email' => $activeUser->email,
                    'last_seen_at' => $activeUser->last_seen_at?->toISOString(),
                    'is_online' => $activeUser->isOnline(),
                ];
            }),
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
