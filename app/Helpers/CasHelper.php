<?php

use App\Services\CasService;

if (!function_exists('cas')) {
    /**
     * Get CAS service instance or perform CAS operation
     * This maintains compatibility with the old subfission/cas package
     * Note: This needs to be registered in the composer.json under the autoload - files.
     */
    function cas(): object
    {
        return new class {
            public function checkAuthentication(): bool
            {
                // Handle masquerade mode first
                if (CasService::isMasquerading()) {
                    return !empty(CasService::getMasqueradeUser());
                }

                return CasService::checkAuthentication();
            }

            public function forceAuthentication(): void
            {
                // Handle masquerade mode
                if (CasService::isMasquerading()) {
                    return; // Skip authentication in masquerade mode
                }

                CasService::forceAuthentication();
            }

            public function forceAuthenticationWithReturnService($clientService): void
            {
                // Handle masquerade mode
                if (CasService::isMasquerading()) {
                    return; // Skip authentication in masquerade mode
                }

                CasService::forceAuthenticationWithReturnService($clientService);
            }
            public function user(): ?string
            {
                // Handle masquerade mode first
                if (CasService::isMasquerading()) {
                    return CasService::getMasqueradeUser();
                }

                return CasService::user();
            }

            public function attributes(): array
            {
                // Handle masquerade mode
                if (CasService::isMasquerading()) {
                    return []; // No attributes in masquerade mode
                }

                return CasService::attributes();
            }

            public function logout(string $redirectUrl = ''): void
            {
                // Handle masquerade mode
                if (CasService::isMasquerading()) {
                    return; // Skip logout in masquerade mode
                }

                CasService::logout($redirectUrl);
            }

            public function getServerLoginURL(): string
            {
                return CasService::getServerLoginURL();
            }

            public function getServerLogoutURL(): string
            {
                return CasService::getServerLogoutURL();
            }

            public function isAuthenticatedFromSession(): bool
            {
                // Handle masquerade mode first
                if (CasService::isMasquerading()) {
                    return !empty(CasService::getMasqueradeUser());
                }

                return CasService::isAuthenticatedFromSession();
            }

            public function setFixedServiceURL(string $serviceUrl): void
            {
                // Handle masquerade mode
                if (CasService::isMasquerading()) {
                    return; // Skip in masquerade mode
                }

                // Initialize CAS and set the service URL
                CasService::initialize();
                if (class_exists('\phpCAS')) {
                    \phpCAS::setFixedServiceURL($serviceUrl);
                }
            }
        };
    }
}
