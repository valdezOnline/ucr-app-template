<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Core\Domain\Impersonation\Contracts\ImpersonationGateway;

/**
 * @deprecated Use Modules\Core\Application\Impersonation\ImpersonationManager via dependency injection.
 */
class ImpersonationService
{
    public const SESSION_KEY = 'impersonation';

    private static function gateway(): ImpersonationGateway
    {
        return app(ImpersonationGateway::class);
    }

    /**
     * Start an impersonation session.
     */
    public static function start(User $impersonator, User $targetUser, Request $request): void
    {
        self::gateway()->start($impersonator, $targetUser, $request);
    }

    /**
     * Stop the active impersonation session and restore the original admin.
     */
    public static function stop(Request $request, string $reason = 'manual'): User
    {
        return self::gateway()->stop($request, $reason);
    }

    /**
     * Determine whether impersonation is currently active.
     */
    public static function isImpersonating(): bool
    {
        return self::gateway()->isImpersonating();
    }

    /**
     * Get the current impersonation state from session.
     *
     * @return array<string, mixed>
     */
    public static function getState(): array
    {
        return self::gateway()->getState();
    }
}
