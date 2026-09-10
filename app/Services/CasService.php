<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class CasService
{
    protected static $initialized = false;

    /**
     * Read user identity from phpCAS session state without making a CAS server call.
     */
    protected static function getSessionCasUser(): ?string
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return null;
        }

        // phpCAS commonly stores data under phpCAS, but keep fallbacks for custom session names.
        $possibleSessionKeys = [
            'phpCAS',
            (string) config('cas.cas_session_name', 'CASAuth'),
            'CASAuth',
        ];

        foreach ($possibleSessionKeys as $sessionKey) {
            if ($sessionKey === '' || !isset($_SESSION[$sessionKey]) || !is_array($_SESSION[$sessionKey])) {
                continue;
            }

            $candidate = $_SESSION[$sessionKey]['user'] ?? null;
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * Resolve CAS connection values from mixed configuration formats.
     *
     * Supports either:
     * - CAS_HOSTNAME=cas.example.edu with CAS_URI=/cas
     * - CAS_HOSTNAME=https://cas.example.edu/cas
     * - CAS_HOSTNAME=cas.example.edu/cas
     */
    protected static function resolveCasConnectionConfig(): array
    {
        $rawHostname = trim((string) config('cas.cas_hostname', ''));
        $configuredUri = trim((string) config('cas.cas_uri', '/cas'));
        $configuredPort = (int) config('cas.cas_port', 443);

        $hostname = $rawHostname;
        $port = $configuredPort;
        $uriFromHost = '';

        $resolvePathFromEndpoint = static function (string $endpoint, string $suffix): string {
            $endpoint = trim($endpoint);
            if ($endpoint === '') {
                return '';
            }

            $urlCandidate = str_contains($endpoint, '://') ? $endpoint : 'https://' . ltrim($endpoint, '/');
            $parts = parse_url($urlCandidate);

            if (!is_array($parts) || empty($parts['path'])) {
                return '';
            }

            $path = $parts['path'];
            if (str_ends_with($path, $suffix)) {
                $path = substr($path, 0, -strlen($suffix));
            }

            if ($path === '') {
                return '';
            }

            return str_starts_with($path, '/') ? $path : '/' . $path;
        };

        if ($rawHostname !== '' && (str_contains($rawHostname, '://') || str_contains($rawHostname, '/'))) {
            $urlCandidate = str_contains($rawHostname, '://') ? $rawHostname : 'https://' . $rawHostname;
            $parts = parse_url($urlCandidate);

            if (is_array($parts)) {
                if (!empty($parts['host'])) {
                    $hostname = $parts['host'];
                }

                if (!empty($parts['port'])) {
                    $port = (int) $parts['port'];
                }

                if (!empty($parts['path']) && $parts['path'] !== '/') {
                    $uriFromHost = $parts['path'];
                }
            }
        }

        $uri = $configuredUri;

        $loginUriBase = $resolvePathFromEndpoint((string) config('cas.cas_login_url', ''), '/login');
        $logoutUriBase = $resolvePathFromEndpoint((string) config('cas.cas_logout_url', ''), '/logout');

        // If CAS_URI is left as default or empty, infer the most specific configured CAS base path.
        if (($configuredUri === '' || $configuredUri === '/cas') && $uriFromHost !== '') {
            $uri = $uriFromHost;

            // Some setups provide hostname up to /protocol while endpoints include /protocol/cas/*.
            // Prefer explicit endpoint-derived path when it includes /cas and hostname path does not.
            if (!str_contains($uriFromHost, '/cas')) {
                if ($loginUriBase !== '' && str_contains($loginUriBase, '/cas')) {
                    $uri = $loginUriBase;
                } elseif ($logoutUriBase !== '' && str_contains($logoutUriBase, '/cas')) {
                    $uri = $logoutUriBase;
                }
            }
        } elseif ($configuredUri === '' || $configuredUri === '/cas') {
            if ($loginUriBase !== '') {
                $uri = $loginUriBase;
            } elseif ($logoutUriBase !== '') {
                $uri = $logoutUriBase;
            }
        }

        if ($uri === '') {
            $uri = '/cas';
        }

        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return [
            'hostname' => $hostname,
            'port' => $port,
            'uri' => $uri,
        ];
    }

    /**
     * Initialize phpCAS client
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        if (self::isMasquerading()) {
            if (!self::hasValidMasqueradeUser()) {
                Log::warning('CasService: CAS masquerade is enabled, but the configured user is missing or inactive; real CAS login is skipped.', [
                    'net_id' => config('cas.cas_masquerade'),
                    'cas_hostname' => config('cas.cas_hostname'),
                    'cas_uri' => config('cas.cas_uri'),
                ]);
            } else {
                Log::debug('CasService: Skipping phpCAS initialization in masquerade mode');
            }
            return;
        }

        $connection = self::resolveCasConnectionConfig();

        // Initialize phpCAS
        \phpCAS::client(
            CAS_VERSION_2_0, // or use config('cas.cas_version')
            $connection['hostname'],
            $connection['port'],
            $connection['uri'],
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

        if (self::isMasquerading()) {
            return !empty(self::getMasqueradeUser());
        }

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
        if (self::isMasquerading()) {
            return !empty(self::getMasqueradeUser());
        }

        try {
            self::initialize();
            return self::getSessionCasUser() !== null;
        } catch (\Exception $e) {
            Log::error('CAS session authentication check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Resolve authenticated CAS user, preferring local session then optional CAS ticket validation.
     */
    public static function authenticatedUser(bool $allowServerCheck = true): ?string
    {
        if (self::isMasquerading()) {
            return self::getMasqueradeUser();
        }

        try {
            self::initialize();

            $sessionUser = self::getSessionCasUser();
            if ($sessionUser !== null) {
                return $sessionUser;
            }

            if (!$allowServerCheck) {
                return null;
            }

            if (\phpCAS::checkAuthentication()) {
                $casUser = \phpCAS::getUser();
                if (is_string($casUser) && trim($casUser) !== '') {
                    return trim($casUser);
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('CAS authenticatedUser resolution error: ' . $e->getMessage());
            return null;
        }
    }
    /**
     * Force CAS authentication
     */
    public static function forceAuthentication(): void
    {
        Log::debug('CasService: Forcing CAS authentication');

        if (self::isMasquerading()) {
            return;
        }

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

        if (self::isMasquerading()) {
            return;
        }

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

        if (self::isMasquerading()) {
            return self::getMasqueradeUser();
        }

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
        if (self::isMasquerading()) {
            return [];
        }

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
        if (self::isMasquerading()) {
            return;
        }

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
        if (self::isMasquerading()) {
            return '';
        }

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
        if (self::isMasquerading()) {
            return '';
        }

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
        if (self::isMasquerading()) {
            return true;
        }

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
     * Check whether the configured masquerade user exists and is active.
     */
    public static function hasValidMasqueradeUser(): bool
    {
        $netId = trim((string) config('cas.cas_masquerade', ''));

        if ($netId === '') {
            return false;
        }

        return \App\Models\User::query()
            ->whereRaw('net_id = ?', [$netId])
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get masqueraded user
     */
    public static function getMasqueradeUser(): ?string
    {
        return config('cas.cas_masquerade');
    }
}
