<?php

namespace App\Services;

use App\Models\ImpersonationAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ImpersonationService
{
    public const SESSION_KEY = 'impersonation';

    /**
     * Start an impersonation session.
     */
    public static function start(User $impersonator, User $targetUser, Request $request): void
    {
        if (self::isImpersonating()) {
            throw new RuntimeException('An impersonation session is already active.');
        }

        if (!$impersonator->hasRole('admin') && !$impersonator->is_admin) {
            throw new RuntimeException('Only administrators can start impersonation.');
        }

        if ($targetUser->id === $impersonator->id) {
            throw new RuntimeException('You cannot impersonate your own account.');
        }

        if (!$targetUser->is_active) {
            throw new RuntimeException('You can only impersonate active users.');
        }

        if ($targetUser->hasRole('admin') || $targetUser->is_admin) {
            throw new RuntimeException('Administrators cannot impersonate other administrators.');
        }

        $audit = ImpersonationAudit::create([
            'impersonator_user_id' => $impersonator->id,
            'impersonated_user_id' => $targetUser->id,
            'auth_type' => config('auth.type', 'laravel'),
            'started_at' => now(),
            'start_ip' => $request->ip(),
            'start_user_agent' => $request->userAgent(),
            'stop_reason' => null,
        ]);

        session([
            self::SESSION_KEY => [
                'active' => true,
                'impersonator_id' => $impersonator->id,
                'impersonator_name' => $impersonator->full_name,
                'impersonator_net_id' => $impersonator->net_id,
                'impersonated_user_id' => $targetUser->id,
                'audit_id' => $audit->id,
                'original_cas_user' => session('cas_user'),
            ],
        ]);

        Auth::login($targetUser);

        if (AuthenticationService::isCasAuth()) {
            session(['cas_user' => $targetUser->net_id]);
        }

        Log::info('Impersonation started', [
            'impersonator_user_id' => $impersonator->id,
            'impersonated_user_id' => $targetUser->id,
            'audit_id' => $audit->id,
            'auth_type' => config('auth.type', 'laravel'),
        ]);
    }

    /**
     * Stop the active impersonation session and restore the original admin.
     */
    public static function stop(Request $request, string $reason = 'manual'): User
    {
        $state = self::getState();

        if (!self::isImpersonating() || empty($state['impersonator_id'])) {
            throw new RuntimeException('No active impersonation session found.');
        }

        $impersonator = User::find($state['impersonator_id']);

        if (!$impersonator || !$impersonator->is_active) {
            throw new RuntimeException('The original administrator account is unavailable.');
        }

        Auth::login($impersonator);

        if (AuthenticationService::isCasAuth()) {
            if (!empty($state['original_cas_user'])) {
                session(['cas_user' => $state['original_cas_user']]);
            } else {
                session()->forget('cas_user');
            }
        }

        if (!empty($state['audit_id'])) {
            $audit = ImpersonationAudit::find($state['audit_id']);

            if ($audit && $audit->ended_at === null) {
                $audit->update([
                    'ended_at' => now(),
                    'end_ip' => $request->ip(),
                    'end_user_agent' => $request->userAgent(),
                    'stop_reason' => $reason,
                ]);
            }
        }

        session()->forget(self::SESSION_KEY);

        Log::info('Impersonation stopped', [
            'impersonator_user_id' => $impersonator->id,
            'reason' => $reason,
        ]);

        return $impersonator;
    }

    /**
     * Determine whether impersonation is currently active.
     */
    public static function isImpersonating(): bool
    {
        $state = self::getState();

        return ($state['active'] ?? false) === true
            && !empty($state['impersonator_id'])
            && !empty($state['impersonated_user_id']);
    }

    /**
     * Get the current impersonation state from session.
     *
     * @return array<string, mixed>
     */
    public static function getState(): array
    {
        $state = session(self::SESSION_KEY, []);

        return is_array($state) ? $state : [];
    }
}
