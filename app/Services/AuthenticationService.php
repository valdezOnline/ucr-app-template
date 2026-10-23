<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class AuthenticationService
{
    /**
     * Get the current authentication type
     */
    public static function getType(): string
    {
        return Config::get('auth.type', 'laravel');
    }

    /**
     * Check if Laravel authentication is enabled
     */
    public static function isLaravelAuth(): bool
    {
        return self::getType() === 'laravel';
    }

    /**
     * Check if CAS authentication is enabled
     */
    public static function isCasAuth(): bool
    {
        return self::getType() === 'cas';
    }

    /**
     * Check if CAS is available and properly configured
     */
    public static function isCasAvailable(): bool
    {
        return self::isCasAuth() &&
            function_exists('cas') &&
            Config::get('cas.cas_hostname') &&
            Config::get('cas.cas_hostname') !== 'cas.myuniv.edu';
    }

    /**
     * Check if CAS masquerade mode is configured with an active local user.
     */
    public static function hasValidCasMasqueradeUser(): bool
    {
        $masqueradeUser = trim((string) Config::get('cas.cas_masquerade', ''));

        if ($masqueradeUser === '') {
            return false;
        }

        return User::where('net_id', $masqueradeUser)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get the appropriate login route based on authentication type
     */
    public static function getLoginRoute(): string
    {
        return route(self::getLoginRouteName());
    }

    /**
     * Get the appropriate login route name based on authentication type
     */
    public static function getLoginRouteName(): string
    {
        if (
            self::isCasAuth() &&
            Route::has('cas.login') &&
            (self::isCasAvailable() || self::hasValidCasMasqueradeUser())
        ) {
            return 'cas.login';
        }

        return 'login';
    }

    /**
     * Get the appropriate logout route based on authentication type
     */
    public static function getLogoutRoute(): string
    {
        return route(self::getLogoutRouteName());
    }

    /**
     * Get the appropriate logout route name based on authentication type
     */
    public static function getLogoutRouteName(): string
    {
        if (self::isCasAuth() && Route::has('cas.logout')) {
            return 'cas.logout';
        }

        return 'logout';
    }

    /**
     * Check if registration should be available
     * Registration is only available for Laravel authentication
     */
    public static function registrationEnabled(): bool
    {
        return self::isLaravelAuth();
    }

    /**
     * Check if password reset should be available
     * Password reset is only available for Laravel authentication
     */
    public static function passwordResetEnabled(): bool
    {
        return self::isLaravelAuth();
    }

    /**
     * Get authentication type display name
     */
    public static function getTypeDisplayName(): string
    {
        return match (self::getType()) {
            'cas' => 'CAS (Single Sign-On)',
            'laravel' => 'Local Authentication',
            default => 'Unknown'
        };
    }

    /**
     * Check if API authentication is available
     */
    public static function isApiAuthAvailable(): bool
    {
        return true; // API authentication is always available
    }

    /**
     * Get authentication methods available
     */
    public static function getAvailableMethods(): array
    {
        $methods = [];

        if (self::isLaravelAuth()) {
            $methods[] = 'laravel';
        }

        if (self::isCasAvailable()) {
            $methods[] = 'cas';
        }

        if (self::isApiAuthAvailable()) {
            $methods[] = 'api';
        }

        return $methods;
    }

    /**
     * Get authentication configuration for external applications
     */
    public static function getApiConfig(): array
    {
        return [
            'type' => self::getType(),
            'cas_available' => self::isCasAvailable(),
            'laravel_available' => self::isLaravelAuth(),
            'registration_enabled' => self::registrationEnabled(),
            'password_reset_enabled' => self::passwordResetEnabled(),
            'api_version' => '1.0',
        ];
    }

    /**
     * Persist authentication type to .env and clear caches.
     */
    public static function persistAuthType(string $type): void
    {
        if (!in_array($type, ['laravel', 'cas'], true)) {
            throw new InvalidArgumentException('Invalid auth type provided.');
        }

        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        if (preg_match('/^AUTH_TYPE=.*$/m', $envContent)) {
            $envContent = preg_replace('/^AUTH_TYPE=.*$/m', "AUTH_TYPE={$type}", $envContent);
        } else {
            $envContent = preg_replace(
                '/^(APP_TIMEZONE=.*$)/m',
                "$1\n\n# Authentication Configuration\n# Options: 'laravel' (default) or 'cas'\nAUTH_TYPE={$type}",
                $envContent
            );
        }

        File::put($envPath, $envContent);

        putenv("AUTH_TYPE={$type}");
        $_ENV['AUTH_TYPE'] = $type;
        $_SERVER['AUTH_TYPE'] = $type;
        Config::set('auth.type', $type);

        Artisan::call('config:clear');
        if (app()->routesAreCached()) {
            Artisan::call('route:clear');
        }
    }
}
