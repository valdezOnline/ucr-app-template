<?php

namespace Modules\Core\Services;

use Modules\Core\Domain\Auth\Contracts\AuthenticationGateway;

/**
 * @deprecated Use Modules\Core\Application\Auth\AuthenticationManager via dependency injection.
 */
class AuthenticationService
{
    private static function gateway(): AuthenticationGateway
    {
        return app(AuthenticationGateway::class);
    }

    /**
     * Get the current authentication type
     */
    public static function getType(): string
    {
        return self::gateway()->getType();
    }

    /**
     * Check if Laravel authentication is enabled
     */
    public static function isLaravelAuth(): bool
    {
        return self::gateway()->isLaravelAuth();
    }

    /**
     * Check if CAS authentication is enabled
     */
    public static function isCasAuth(): bool
    {
        return self::gateway()->isCasAuth();
    }

    /**
     * Check if CAS is available and properly configured
     */
    public static function isCasAvailable(): bool
    {
        return self::gateway()->isCasAvailable();
    }

    /**
     * Check if CAS masquerade mode is configured with an active local user.
     */
    public static function hasValidCasMasqueradeUser(): bool
    {
        return self::gateway()->hasValidCasMasqueradeUser();
    }

    /**
     * Get the appropriate login route based on authentication type
     */
    public static function getLoginRoute(): string
    {
        return self::gateway()->getLoginRoute();
    }

    /**
     * Get the appropriate login route name based on authentication type
     */
    public static function getLoginRouteName(): string
    {
        return self::gateway()->getLoginRouteName();
    }

    /**
     * Get the appropriate logout route based on authentication type
     */
    public static function getLogoutRoute(): string
    {
        return self::gateway()->getLogoutRoute();
    }

    /**
     * Get the appropriate logout route name based on authentication type
     */
    public static function getLogoutRouteName(): string
    {
        return self::gateway()->getLogoutRouteName();
    }

    /**
     * Check if registration should be available
     * Registration is only available for Laravel authentication
     */
    public static function registrationEnabled(): bool
    {
        return self::gateway()->registrationEnabled();
    }

    /**
     * Check if password reset should be available
     * Password reset is only available for Laravel authentication
     */
    public static function passwordResetEnabled(): bool
    {
        return self::gateway()->passwordResetEnabled();
    }

    /**
     * Get authentication type display name
     */
    public static function getTypeDisplayName(): string
    {
        return self::gateway()->getTypeDisplayName();
    }

    /**
     * Check if API authentication is available
     */
    public static function isApiAuthAvailable(): bool
    {
        return self::gateway()->isApiAuthAvailable();
    }

    /**
     * Get authentication methods available
     */
    public static function getAvailableMethods(): array
    {
        return self::gateway()->getAvailableMethods();
    }

    /**
     * Get authentication configuration for external applications
     */
    public static function getApiConfig(): array
    {
        return self::gateway()->getApiConfig();
    }

    /**
     * Persist authentication type to .env and clear caches.
     */
    public static function persistAuthType(string $type): void
    {
        self::gateway()->persistAuthType($type);
    }
}
