<?php

namespace Modules\Core\Application\Impersonation;

use Modules\Core\Models\User;
use Illuminate\Http\Request;
use Modules\Core\Domain\Impersonation\Contracts\ImpersonationGateway;

class ImpersonationManager
{
    public function __construct(
        private readonly ImpersonationGateway $gateway,
    ) {
    }

    public function start(User $impersonator, User $targetUser, Request $request): void
    {
        $this->gateway->start($impersonator, $targetUser, $request);
    }

    public function stop(Request $request, string $reason = 'manual'): User
    {
        return $this->gateway->stop($request, $reason);
    }

    public function isImpersonating(): bool
    {
        return $this->gateway->isImpersonating();
    }
}
