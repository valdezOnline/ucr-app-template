<?php

namespace Modules\Core\Domain\Impersonation\Contracts;

use Modules\Core\Models\User;
use Illuminate\Http\Request;

interface ImpersonationGateway
{
    public function start(User $impersonator, User $targetUser, Request $request): void;

    public function stop(Request $request, string $reason = 'manual'): User;

    public function isImpersonating(): bool;

    /**
     * @return array<string, mixed>
     */
    public function getState(): array;
}
