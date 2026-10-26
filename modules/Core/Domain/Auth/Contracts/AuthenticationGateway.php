<?php

namespace Modules\Core\Domain\Auth\Contracts;

interface AuthenticationGateway
{
    public function getType(): string;

    public function isLaravelAuth(): bool;

    public function isCasAuth(): bool;

    public function isCasAvailable(): bool;

    public function hasValidCasMasqueradeUser(): bool;

    public function getLoginRoute(): string;

    public function getLoginRouteName(): string;

    public function getLogoutRoute(): string;

    public function getLogoutRouteName(): string;

    public function registrationEnabled(): bool;

    public function passwordResetEnabled(): bool;

    public function getTypeDisplayName(): string;

    public function isApiAuthAvailable(): bool;

    /**
     * @return array<int, string>
     */
    public function getAvailableMethods(): array;

    /**
     * @return array<string, mixed>
     */
    public function getApiConfig(): array;

    public function persistAuthType(string $type): void;
}
