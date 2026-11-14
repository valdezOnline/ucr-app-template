<?php

namespace Modules\Core\Services;

use Illuminate\Http\Request;
use Modules\Core\Models\User;

class UserActivityTracker
{
    public function recordLogin(User $user, ?Request $request = null): void
    {
        $this->recordUserActivity($user, 'login', $request, [
            'last_login_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    public function recordLogout(User $user, ?Request $request = null): void
    {
        $this->recordUserActivity($user, 'logout', $request, [
            'last_logout_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    public function recordLastSeen(User $user, ?Request $request = null): void
    {
        $this->recordUserActivity($user, 'last_seen', $request, [
            'last_seen_at' => now(),
        ]);
    }

    public function touchLastSeen(User $user, int $minimumIntervalSeconds = 60): void
    {
        if ($minimumIntervalSeconds > 0
            && $user->last_seen_at !== null
            && $user->last_seen_at->greaterThan(now()->subSeconds($minimumIntervalSeconds))) {
            return;
        }

        $user->forceFill([
            'last_seen_at' => now(),
        ])->saveQuietly();
    }

    protected function recordUserActivity(User $user, string $event, ?Request $request, array $userAttributes): void
    {
        $user->forceFill($userAttributes)->saveQuietly();

        $user->activities()->create([
            'event' => $event,
            'session_id' => $request?->session()->getId() ?? session()->getId(),
            'ip_address' => $request?->ip() ?? request()->ip(),
            'user_agent' => $request?->userAgent() ?? request()->userAgent(),
            'metadata' => [
                'path' => $request?->path(),
                'route' => optional($request?->route())->getName(),
            ],
        ]);
    }
}
