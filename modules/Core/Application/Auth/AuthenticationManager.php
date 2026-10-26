<?php

namespace Modules\Core\Application\Auth;

use Modules\Core\Domain\Auth\Contracts\AuthenticationGateway;

class AuthenticationManager
{
    public function __construct(
        private readonly AuthenticationGateway $gateway,
    ) {
    }

    public function getType(): string
    {
        return $this->gateway->getType();
    }

    public function isCasAvailable(): bool
    {
        return $this->gateway->isCasAvailable();
    }

    public function isCasAuth(): bool
    {
        return $this->gateway->isCasAuth();
    }

    public function isLaravelAuth(): bool
    {
        return $this->gateway->isLaravelAuth();
    }

    public function hasValidCasMasqueradeUser(): bool
    {
        return $this->gateway->hasValidCasMasqueradeUser();
    }

    public function getLoginRouteName(): string
    {
        return $this->gateway->getLoginRouteName();
    }

    public function getLogoutRouteName(): string
    {
        return $this->gateway->getLogoutRouteName();
    }

    public function registrationEnabled(): bool
    {
        return $this->gateway->registrationEnabled();
    }

    public function passwordResetEnabled(): bool
    {
        return $this->gateway->passwordResetEnabled();
    }

    public function getTypeDisplayName(): string
    {
        return $this->gateway->getTypeDisplayName();
    }

    /**
     * @return array<string, mixed>
     */
    public function getApiConfiguration(): array
    {
        return $this->gateway->getApiConfig();
    }

    public function switchAuthenticationType(string $type): void
    {
        $this->gateway->persistAuthType($type);
    }
}
