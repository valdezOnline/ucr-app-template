<?php

namespace Modules\Core\Infrastructure\Impersonation;

use App\Models\ImpersonationAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Core\Domain\Auth\Contracts\AuthenticationGateway;
use Modules\Core\Domain\Impersonation\Contracts\ImpersonationGateway;
use RuntimeException;

class LegacyImpersonationGateway implements ImpersonationGateway
{
    private const SESSION_KEY = 'impersonation';

    public function __construct(
        private readonly AuthenticationGateway $authenticationGateway,
    ) {
    }

    public function start(User $impersonator, User $targetUser, Request $request): void
    {
        if ($this->isImpersonating()) {
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

        if ($this->authenticationGateway->isCasAuth()) {
            session(['cas_user' => $targetUser->net_id]);
        }

        Log::info('Impersonation started', [
            'impersonator_user_id' => $impersonator->id,
            'impersonated_user_id' => $targetUser->id,
            'audit_id' => $audit->id,
            'auth_type' => config('auth.type', 'laravel'),
        ]);
    }

    public function stop(Request $request, string $reason = 'manual'): User
    {
        $state = $this->getState();

        if (!$this->isImpersonating() || empty($state['impersonator_id'])) {
            throw new RuntimeException('No active impersonation session found.');
        }

        $impersonator = User::find($state['impersonator_id']);

        if (!$impersonator || !$impersonator->is_active) {
            throw new RuntimeException('The original administrator account is unavailable.');
        }

        Auth::login($impersonator);

        if ($this->authenticationGateway->isCasAuth()) {
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

    public function isImpersonating(): bool
    {
        $state = $this->getState();

        return ($state['active'] ?? false) === true
            && !empty($state['impersonator_id'])
            && !empty($state['impersonated_user_id']);
    }

    public function getState(): array
    {
        $state = session(self::SESSION_KEY, []);

        return is_array($state) ? $state : [];
    }
}
