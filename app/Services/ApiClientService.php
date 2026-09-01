<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class ApiClientService
{
    private string $baseUrl;
    private string $appName;
    private string $appKey;
    private ?string $currentToken = null;
    private ?string $tokenExpiry = null;

    public function __construct()
    {
        $this->baseUrl = config('app.api_app_url') ?? env('API_APP_URL', '');
        $this->appName = config('app.api_app_name') ?? env('API_APP_NAME', '');
        $this->appKey = config('app.api_app_key') ?? env('API_APP_KEY', '');

        // Validate required configuration
        if (!$this->baseUrl || !$this->appName || !$this->appKey) {
            throw new Exception('Missing required API configuration: Please check that the following environment variables are set: API_APP_URL, API_APP_NAME, or API_APP_KEY');
        }
    }

    /**
     * Get a valid bearer token, refreshing if necessary
     */
    private function getBearerToken(): string
    {
        // Check if we have a cached token that's still valid
        $cacheKey = 'api_bearer_token';
        $tokenData = Cache::get($cacheKey);

        if ($tokenData && isset($tokenData['token']) && isset($tokenData['expires_at'])) {
            // Check if token is still valid (with 5 minute buffer)
            if (now()->addMinutes(5)->isBefore($tokenData['expires_at'])) {
                $this->currentToken = $tokenData['token'];
                return $this->currentToken;
            }
        }

        // Token expired or doesn't exist, request a new one
        return $this->requestNewToken();
    }

    /**
     * Request a new bearer token from the API
     */
    private function requestNewToken(): string
    {
        try {
            $tokenUrl = rtrim($this->baseUrl, '/') . "/api/v1/entry";

            Log::info('Requesting new bearer token', [
                'token_url' => $tokenUrl,
                'application' => $this->appName
            ]);

            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->post($tokenUrl, [
                    'application' => $this->appName,
                    'apikey' => $this->appKey
                ]);

            if (!$response->successful()) {
                throw new Exception("Token request failed with status: {$response->status()}. Body: {$response->body()}");
            }

            $data = $response->json();
            $token = $data['data']['token'] ?? $data['token'] ?? $data['access_token'] ?? $data['bearer_token'] ?? null;

            if (!$token) {
                throw new Exception('Token not found in API response. Available keys: ' . implode(', ', array_keys($data ?? [])));
            }

            // Cache the token with expiration time (default to 20 hours as per LibraryPublicApiService)
            $expiresAt = now()->addHours(20);
            $cacheKey = 'api_bearer_token';

            Cache::put($cacheKey, [
                'token' => $token,
                'expires_at' => $expiresAt
            ], $expiresAt);

            $this->currentToken = $token;

            Log::info('Successfully obtained and cached new bearer token', [
                'expires_at' => $expiresAt->toISOString()
            ]);

            return $token;

        } catch (Exception $e) {
            Log::error('Failed to request new bearer token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Make an authenticated HTTP GET request with automatic token renewal
     */
    public function get(string $endpoint, array $queryParams = [], array $headers = []): array
    {
        return $this->makeAuthenticatedRequest('GET', $endpoint, $queryParams, $headers);
    }

    /**
     * Make an authenticated HTTP POST request with automatic token renewal
     */
    public function post(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->makeAuthenticatedRequest('POST', $endpoint, $data, $headers);
    }

    /**
     * Make an authenticated HTTP PUT request with automatic token renewal
     */
    public function put(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->makeAuthenticatedRequest('PUT', $endpoint, $data, $headers);
    }

    /**
     * Make an authenticated HTTP DELETE request with automatic token renewal
     */
    public function delete(string $endpoint, array $data = [], array $headers = []): array
    {
        return $this->makeAuthenticatedRequest('DELETE', $endpoint, $data, $headers);
    }

    /**
     * Make an authenticated request with automatic token renewal on 401 responses
     */
    private function makeAuthenticatedRequest(string $method, string $endpoint, array $data = [], array $headers = [], int $retryCount = 0): array
    {
        $maxRetries = 2; // Allow up to 2 retries for token renewal

        if ($retryCount > $maxRetries) {
            throw new Exception('Maximum retry attempts reached for API request');
        }

        try {
            // Get current token
            $token = $this->getBearerToken();

            // Build full URL
            $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

            // Prepare headers with authorization
            $requestHeaders = array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ], $headers);

            // Log the request (excluding sensitive data)
            Log::info('Making authenticated API request', [
                'method' => $method,
                'url' => $url,
                'retry_count' => $retryCount,
                'has_data' => !empty($data)
            ]);

            // Make the request
            $httpClient = Http::timeout(10)->withHeaders($requestHeaders);

            $response = match (strtoupper($method)) {
                'GET' => $httpClient->get($url, $data),
                'POST' => $httpClient->post($url, $data),
                'PUT' => $httpClient->put($url, $data),
                'DELETE' => $httpClient->delete($url, $data),
                default => throw new Exception("Unsupported HTTP method: {$method}")
            };

            // Check if token expired (401 Unauthorized)
            if ($response->status() === 401) {
                Log::warning('API request returned 401 - token may have expired, attempting renewal', [
                    'method' => $method,
                    'url' => $url,
                    'retry_count' => $retryCount
                ]);

                // Clear cached token to force renewal
                Cache::forget('api_bearer_token');
                $this->currentToken = null;

                // Retry the request with a new token
                return $this->makeAuthenticatedRequest($method, $endpoint, $data, $headers, $retryCount + 1);
            }

            // Check if request was successful
            if (!$response->successful()) {
                throw new Exception("API request failed with status: {$response->status()}. Body: {$response->body()}");
            }

            $responseData = $response->json();

            Log::info('API request successful', [
                'method' => $method,
                'url' => $url,
                'status' => $response->status(),
                'has_response_data' => !empty($responseData)
            ]);

            return $responseData;

        } catch (Exception $e) {
            Log::error('API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'retry_count' => $retryCount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


    /**
     * Get net_id from card_id
     */
    public function getNetIdFromIsoCardId(string $cardId): ?string
    {
        try {
            $response = $this->get("api/v1/ucr-card-data/iso/{$cardId}");

            // Extract net_id from the response
            $netId = $response['data']['net_id'] ?? $response['net_id'] ?? $response['netId'] ?? $response['netid'] ?? null;

            if ($netId) {
                Log::info('Successfully retrieved net_id from card data', [
                    'card_id' => $cardId,
                    'net_id' => $netId
                ]);
                return $netId;
            } else {
                Log::warning('net_id not found in API response', [
                    'card_id' => $cardId,
                    'response_keys' => array_keys($response ?? [])
                ]);
                return null;
            }

        } catch (Exception $e) {
            Log::error('Failed to get net_id from card_id', [
                'card_id' => $cardId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get net_id from barcode_id
     */
    public function getNetIdFromBarcodeId(string $barcodeId): ?string
    {
        try {
            $response = $this->get("api/v1/ucr-card-data/library-number/{$barcodeId}");

            // Extract net_id from the response
            $netId = $response['data']['net_id'] ?? $response['net_id'] ?? $response['netId'] ?? $response['netid'] ?? null;

            if ($netId) {
                Log::info('Successfully retrieved net_id from barcode data', [
                    'barcode_id' => $barcodeId,
                    'net_id' => $netId
                ]);
                return $netId;
            } else {
                Log::warning('net_id not found in API response', [
                    'barcode_id' => $barcodeId,
                    'response_keys' => array_keys($response ?? [])
                ]);
                return null;
            }

        } catch (Exception $e) {
            Log::error('Failed to get net_id from barcode_id', [
                'barcode_id' => $barcodeId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get list of user in University Library Department
     */
    public function getUsersInLibraryDepartment(): ?array
    {
        try {
            $response = $this->get("api/v1/ucr-person/homeDepartment/University Library");

            $users = $response['data'] ?? null;

            if ($users && is_array($users)) {
                // Sort by lastName attribute
                usort($users, function ($a, $b) {
                    $lastNameA = $a['firstName'] ?? '';
                    $lastNameB = $b['firstName'] ?? '';
                    return strcasecmp($lastNameA, $lastNameB);
                });
            }

            return $users;

        } catch (Exception $e) {
            Log::error('Failed to get users in University Library Department', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getUcrPersonInfo(string $netId): ?array
    {
        try {
            $response = $this->get("api/v1/ucr-person/{$netId}");

            // Extract participant info from the response
            $participantInfo = [
                'first_name' => $response['data']['firstName'] ?? $response['firstName'] ?? null,
                'last_name' => $response['data']['lastName'] ?? $response['lastName'] ?? null,
                'email' => $response['data']['emailAddress'] ?? $response['emailAddress'] ?? $response['email'] ?? null,
                'net_id' => $netId,
                'primary_affiliation' => $response['data']['eduPersonPrimaryAffiliation'] ?? null,
                'organization' => $response['data']['ucrOrg'] ?? null,
                'department' => $response['data']['homeDepartment'] ?? null,
                'ucr_college' => $response['data']['ucrCollege'] ?? null,
                'ucr_class_standing' => $response['data']['ucrClassStanding'] ?? null,
            ];

            // Filter out null values
            $participantInfo = array_filter($participantInfo, function ($value) {
                return $value !== null;
            });

            return !empty($participantInfo) ? $participantInfo : null;

        } catch (Exception $e) {
            Log::error('Failed to get participant info from net_id', [
                'net_id' => $netId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get current bearer token for external use (if needed)
     */
    public function getCurrentToken(): string
    {
        return $this->getBearerToken();
    }

    /**
     * Get HrPerson info by net_id
     */
    public function getHrPersonInfo(string $netId): ?array
    {
        try {
            $response = $this->get("api/v1/hr/employee/{$netId}");

            // Extract HrPerson info from the response
            // $hrPersonInfo = $response['data'] ?? null;
            $hrPersonInfo = [
                'net_id' => $response['data']['netId'] ?? null,
                'first_name' => $response['data']['firstName'] ?? null,
                'last_name' => $response['data']['lastName'] ?? null,
                'department' => $response['data']['departmentCodeDescription'] ?? null,
                'title' => $response['data']['jobCodeDescription'] ?? null,
                'supervisor_net_id' => $response['data']['supervisorNetId'] ?? null,
                'supervisor_name' => $response['data']['supervisorFullName'] ?? null,
                'email' => $response['data']['emailAddress'] ?? null,
            ];


            if ($hrPersonInfo) {
                Log::info('Successfully retrieved HrPerson info', [
                    'net_id' => $netId
                ]);
                return $hrPersonInfo;
            } else {
                Log::warning('HrPerson info not found in API response', [
                    'net_id' => $netId,
                    'response_keys' => array_keys($response ?? [])
                ]);
                return null;
            }

        } catch (Exception $e) {
            Log::error('Failed to get HrPerson info from net_id', [
                'net_id' => $netId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Force refresh of bearer token
     */
    public function refreshToken(): string
    {
        Cache::forget('api_bearer_token');
        $this->currentToken = null;
        return $this->requestNewToken();
    }
}
