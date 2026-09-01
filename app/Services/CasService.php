<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class CasService
{
    protected static $initialized = false;

    /**
     * Initialize phpCAS client
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Initialize phpCAS
        \phpCAS::client(
            CAS_VERSION_2_0, // or use config('cas.cas_version')
            config('cas.cas_hostname'),
            (int) config('cas.cas_port', 443),
            config('cas.cas_uri', '/cas'),
            config('cas.cas_client_service', false) // service_base_url parameter
        );

        // Set the CA certificate that is the issuer of the certificate
        // on the CAS server
        if (config('cas.cas_validation') === 'ca' && config('cas.cas_cert')) {
            \phpCAS::setCasServerCACert(config('cas.cas_cert'));
        } elseif (config('cas.cas_validation') === 'self') {
            \phpCAS::setNoCasServerValidation();
        } else {
            // For development/testing only - disable SSL validation
            \phpCAS::setNoCasServerValidation();
        }

        // Set service URL if specified
        if (config('cas.cas_client_service')) {
            \phpCAS::setFixedServiceURL(config('cas.cas_client_service'));
        }

        // Enable debug mode if configured
        if (config('cas.cas_debug')) {
            if (is_string(config('cas.cas_debug'))) {
                \phpCAS::setDebug(config('cas.cas_debug'));
            } else {
                \phpCAS::setDebug();
            }
        }

        // Set verbose errors if configured
        if (config('cas.cas_verbose_errors')) {
            \phpCAS::setVerbose(true);
        }

        self::$initialized = true;
    }

    /**
     * Check if user is authenticated via CAS
     */
    public static function checkAuthentication(): bool
    {
        Log::debug('CasService: Checking CAS authentication status');
        try {
            self::initialize();
            // Log::debug('CasService: CAS initialized successfully');
            return \phpCAS::checkAuthentication();
        } catch (\Exception $e) {
            Log::error('CasService: checkAuthentication error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is authenticated from session only (no CAS server call)
     * This avoids the gateway=true parameter being added to service URLs
     */
    public static function isAuthenticatedFromSession(): bool
    {
        try {
            self::initialize();
            // Check if phpCAS has a valid authenticated session
            // We check the internal phpCAS session state without making a server call
            return isset($_SESSION['phpCAS']) &&
                isset($_SESSION['phpCAS']['user']) &&
                !empty($_SESSION['phpCAS']['user']);
        } catch (\Exception $e) {
            Log::error('CAS session authentication check error: ' . $e->getMessage());
            return false;
        }
    }
    /**
     * Force CAS authentication
     */
    public static function forceAuthentication(): void
    {
        Log::debug('CasService: Forcing CAS authentication');
        try {
            self::initialize();
            Log::debug('CasService: CAS initialized successfully');
            \phpCAS::forceAuthentication();
        } catch (\Exception $e) {
            Log::error('CasService: forceAuthentication error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Force CAS authentication with specific client service
     */
    public static function forceAuthenticationWithReturnService($clientService): void
    {
        Log::debug('CasService: Forcing CAS authentication');
        try {
            self::initialize();
            Log::debug('CasService: CAS initialized successfully');
            \phpCAS::setFixedServiceURL($clientService);
            \phpCAS::forceAuthentication();
        } catch (\Exception $e) {
            Log::error('CasService: forceAuthentication error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the authenticated user's identifier
     */
    public static function user(): ?string
    {
        Log::debug('CasService: Checking CAS user');
        try {
            self::initialize();
            if (\phpCAS::checkAuthentication()) {
                Log::debug('CasService: User is CAS authenticated');
                return \phpCAS::getUser();
            }
            return null;
        } catch (\Exception $e) {
            Log::error('CAS getUser error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get CAS user attributes (if SAML is enabled)
     */
    public static function attributes(): array
    {
        try {
            self::initialize();
            if (\phpCAS::checkAuthentication()) {
                return \phpCAS::getAttributes();
            }
            return [];
        } catch (\Exception $e) {
            Log::error('CAS getAttributes error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Logout from CAS
     */
    public static function logout(string $redirectUrl = ''): void
    {
        try {
            self::initialize();
            if ($redirectUrl) {
                \phpCAS::logoutWithRedirectService($redirectUrl);
            } else {
                \phpCAS::logout();
            }
        } catch (\Exception $e) {
            Log::error('CAS logout error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get CAS server login URL
     */
    public static function getServerLoginURL(): string
    {
        try {
            self::initialize();
            return \phpCAS::getServerLoginURL();
        } catch (\Exception $e) {
            Log::error('CAS getServerLoginURL error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Get CAS server logout URL
     */
    public static function getServerLogoutURL(): string
    {
        try {
            self::initialize();
            return \phpCAS::getServerLogoutURL();
        } catch (\Exception $e) {
            Log::error('CAS getServerLogoutURL error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Check if CAS is available/configured
     */
    public static function isAvailable(): bool
    {
        $hostname = config('cas.cas_hostname');
        return !empty($hostname) && $hostname !== 'cas.myuniv.edu';
    }

    /**
     * Handle masquerade mode for development
     */
    public static function isMasquerading(): bool
    {
        return !empty(config('cas.cas_masquerade'));
    }

    /**
     * Get masqueraded user
     */
    public static function getMasqueradeUser(): ?string
    {
        return config('cas.cas_masquerade');
    }
}
