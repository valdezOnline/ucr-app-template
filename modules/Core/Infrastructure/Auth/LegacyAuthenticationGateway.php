<?php

namespace Modules\Core\Infrastructure\Auth;

use Modules\Core\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Modules\Core\Domain\Auth\Contracts\AuthenticationGateway;

class LegacyAuthenticationGateway implements AuthenticationGateway
{
    public function getType(): string
    {
        return Config::get('auth.type', 'laravel');
    }

    public function isLaravelAuth(): bool
    {
        return $this->getType() === 'laravel';
    }

    public function isCasAuth(): bool
    {
        return $this->getType() === 'cas';
    }

    public function isCasAvailable(): bool
    {
        return $this->isCasAuth()
            && function_exists('cas')
            && Config::get('cas.cas_hostname')
            && Config::get('cas.cas_hostname') !== 'cas.myuniv.edu';
    }

    public function hasValidCasMasqueradeUser(): bool
    {
        $masqueradeUser = trim((string) Config::get('cas.cas_masquerade', ''));

        if ($masqueradeUser === '') {
            return false;
        }

        return User::where('net_id', $masqueradeUser)
            ->where('is_active', true)
            ->exists();
    }

    public function getLoginRoute(): string
    {
        return route($this->getLoginRouteName());
    }

    public function getLoginRouteName(): string
    {
        if (
            $this->isCasAuth()
            && Route::has('cas.login')
            && ($this->isCasAvailable() || $this->hasValidCasMasqueradeUser())
        ) {
            return 'cas.login';
        }

        return 'login';
    }

    public function getLogoutRoute(): string
    {
        return route($this->getLogoutRouteName());
    }

    public function getLogoutRouteName(): string
    {
        if ($this->isCasAuth() && Route::has('cas.logout')) {
            return 'cas.logout';
        }

        return 'logout';
    }

    public function registrationEnabled(): bool
    {
        return $this->isLaravelAuth();
    }

    public function passwordResetEnabled(): bool
    {
        return $this->isLaravelAuth();
    }

    public function getTypeDisplayName(): string
    {
        return match ($this->getType()) {
            'cas' => 'CAS (Single Sign-On)',
            'laravel' => 'Local Authentication',
            default => 'Unknown',
        };
    }

    public function isApiAuthAvailable(): bool
    {
        return true;
    }

    public function getAvailableMethods(): array
    {
        $methods = [];

        if ($this->isLaravelAuth()) {
            $methods[] = 'laravel';
        }

        if ($this->isCasAvailable()) {
            $methods[] = 'cas';
        }

        if ($this->isApiAuthAvailable()) {
            $methods[] = 'api';
        }

        return $methods;
    }

    public function getApiConfig(): array
    {
        return [
            'type' => $this->getType(),
            'cas_available' => $this->isCasAvailable(),
            'laravel_available' => $this->isLaravelAuth(),
            'registration_enabled' => $this->registrationEnabled(),
            'password_reset_enabled' => $this->passwordResetEnabled(),
            'api_version' => '1.0',
        ];
    }

    public function persistAuthType(string $type): void
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
