<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ExternalAuthorizationCode;
use App\Models\User;
use App\Services\AuthenticationService;
// use App\Services\CasService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Start external browser-based authorization using CAS.
     */
    public function authorizeExternal(Request $request)
    {
        if (!config('auth.external_broker.enabled')) {
            Log::warning('External auth authorize rejected: broker disabled');
            return response()->json([
                'success' => false,
                'message' => 'External authentication broker is disabled'
            ], 404);
        }

        $validated = $request->validate([
            'api_key' => 'required|string',
            'redirect_uri' => 'required|url|max:2048',
            'state' => 'required|string|max:255',
        ]);

        Log::info('External auth authorize requested', [
            'api_key_prefix' => substr($validated['api_key'], 0, 12),
            'redirect_uri' => $validated['redirect_uri'],
            'state_present' => !empty($validated['state']),
            'ip' => $request->ip(),
        ]);

        $application = Application::where('api_key', $validated['api_key'])->first();
        if (!$application || !$application->canUseExternalAuthBroker()) {
            Log::warning('External auth authorize rejected: invalid client configuration', [
                'api_key_prefix' => substr($validated['api_key'], 0, 12),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid client configuration for external authentication'
            ], 401);
        }

        if (!$application->isValidExternalCallback($validated['redirect_uri'])) {
            Log::warning('External auth authorize rejected: redirect URI mismatch', [
                'application_id' => $application->id,
                'redirect_uri' => $validated['redirect_uri'],
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid redirect URI for this application'
            ], 422);
        }

        if (!$this->isAllowedRedirectScheme($validated['redirect_uri'])) {
            Log::warning('External auth authorize rejected: redirect URI scheme not allowed', [
                'application_id' => $application->id,
                'redirect_uri' => $validated['redirect_uri'],
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Redirect URI must use HTTPS'
            ], 422);
        }

        $requestId = (string) Str::uuid();

        session([
            'external_auth.pending' => [
                'request_id' => $requestId,
                'application_id' => $application->id,
                'redirect_uri' => $validated['redirect_uri'],
                'state' => $validated['state'],
                'expires_at' => now()->addMinutes((int) config('auth.external_broker.authorization_ttl_minutes', 10))->timestamp,
            ],
        ]);

        Log::info('External auth authorize session prepared', [
            'request_id' => $requestId,
            'application_id' => $application->id,
            'expires_at' => session('external_auth.pending.expires_at'),
        ]);

        if (!function_exists('cas')) {
            Log::error('External auth authorize failed: CAS service missing', [
                'request_id' => $requestId,
                'application_id' => $application->id,
            ]);
            return $this->redirectExternalError(
                $validated['redirect_uri'],
                $validated['state'],
                'server_error',
                'CAS service is not available'
            );
        }

        try {
            if (!cas()->isAuthenticatedFromSession()) {
                cas()->forceAuthenticationWithReturnService(route('api.auth.external.callback', ['rid' => $requestId]));
            }

            return $this->completeExternalAuthorization($requestId);
        } catch (\Throwable $e) {
            Log::error('External authorize flow failed', [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
            ]);

            return $this->redirectExternalError(
                $validated['redirect_uri'],
                $validated['state'],
                'server_error',
                'Authentication service failed'
            );
        }
    }

    /**
     * Handle CAS callback and redirect back with an authorization code.
     */
    public function callbackExternal(Request $request)
    {
        Log::info('External auth callback received', [
            'request_id' => $request->query('rid'),
            'ip' => $request->ip(),
        ]);

        return $this->completeExternalAuthorization($request->query('rid'));
    }

    /**
     * Exchange authorization code for Sanctum bearer token.
     */
    public function exchangeExternalToken(Request $request): JsonResponse
    {
        if (!config('auth.external_broker.enabled')) {
            Log::warning('External auth token exchange rejected: broker disabled');
            return response()->json([
                'success' => false,
                'error' => 'server_error',
                'message' => 'External authentication broker is disabled',
            ], 404);
        }

        $validated = $request->validate([
            'grant_type' => 'required|string|in:authorization_code',
            'code' => 'required|string|min:32',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
            'redirect_uri' => 'required|url|max:2048',
        ]);

        Log::info('External auth token exchange requested', [
            'api_key_prefix' => substr($validated['api_key'], 0, 12),
            'redirect_uri' => $validated['redirect_uri'],
            'ip' => $request->ip(),
        ]);

        $application = $this->verifyApplicationCredentials(
            $validated['api_key'],
            $validated['api_secret'],
            parse_url($validated['redirect_uri'], PHP_URL_HOST)
        );

        if (!$application || !$application->canUseExternalAuthBroker()) {
            Log::warning('External auth token exchange rejected: invalid_client', [
                'api_key_prefix' => substr($validated['api_key'], 0, 12),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'invalid_client',
                'message' => 'Invalid client credentials',
            ], 401);
        }

        if (!$application->isValidExternalCallback($validated['redirect_uri'])) {
            Log::warning('External auth token exchange rejected: redirect_uri mismatch', [
                'application_id' => $application->id,
                'redirect_uri' => $validated['redirect_uri'],
            ]);
            return response()->json([
                'success' => false,
                'error' => 'invalid_grant',
                'message' => 'Redirect URI mismatch',
            ], 400);
        }

        $codeHash = hash('sha256', $validated['code']);

        /** @var ExternalAuthorizationCode|null $authCode */
        $authCode = ExternalAuthorizationCode::query()
            ->where('application_id', $application->id)
            ->where('code_hash', $codeHash)
            ->first();

        if (!$authCode || $authCode->isConsumed() || $authCode->isExpired()) {
            Log::warning('External auth token exchange rejected: invalid_grant', [
                'application_id' => $application->id,
                'code_found' => (bool) $authCode,
                'is_consumed' => $authCode ? $authCode->isConsumed() : null,
                'is_expired' => $authCode ? $authCode->isExpired() : null,
            ]);
            return response()->json([
                'success' => false,
                'error' => 'invalid_grant',
                'message' => 'Authorization code is invalid, consumed, or expired',
            ], 400);
        }

        if (rtrim($authCode->redirect_uri, '/') !== rtrim($validated['redirect_uri'], '/')) {
            Log::warning('External auth token exchange rejected: code redirect mismatch', [
                'application_id' => $application->id,
                'code_id' => $authCode->id,
            ]);
            return response()->json([
                'success' => false,
                'error' => 'invalid_grant',
                'message' => 'Authorization code redirect URI mismatch',
            ], 400);
        }

        $user = $authCode->user;
        if (!$user || !$user->is_active || !$application->hasAccess($user)) {
            Log::warning('External auth token exchange rejected: unauthorized_user', [
                'application_id' => $application->id,
                'user_id' => $user?->id,
                'user_active' => $user?->is_active,
            ]);
            return response()->json([
                'success' => false,
                'error' => 'unauthorized_user',
                'message' => 'User is no longer authorized for this application',
            ], 403);
        }

        $authCode->update(['consumed_at' => now()]);

        $tokenResult = $user->createToken($application->name . ' External Broker Access');
        $expiration = config('sanctum.expiration');
        $expiresAt = $expiration ? now()->addMinutes((int) $expiration)->toISOString() : null;

        Log::info('External auth token issued', [
            'application_id' => $application->id,
            'user_id' => $user->id,
            'code_id' => $authCode->id,
            'token_id' => $tokenResult->accessToken->id ?? null,
        ]);

        return response()->json([
            'success' => true,
            'token_type' => 'Bearer',
            'access_token' => $tokenResult->plainTextToken,
            'expires_at' => $expiresAt,
            'user' => [
                'id' => $user->id,
                'net_id' => $user->net_id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->toArray(),
            ],
            'application' => [
                'id' => $application->id,
                'name' => $application->name,
            ],
        ]);
    }

    /**
     * Authenticate a user for external applications.
     */
    public function authenticate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'api_key' => 'sometimes|string',
                'api_secret' => 'sometimes|string',
                'net_id' => 'required|string',
                'password' => 'sometimes|string|nullable',
                'domain' => 'sometimes|string|nullable',
            ]);

            // Get API credentials from headers if not in body (support multiple header formats)
            $apiKey = $validated['api_key'] ??
                $request->header('x-api-key') ??
                $request->header('X-API-Key') ??
                $request->header('X-Api-Key');

            $apiSecret = $validated['api_secret'] ??
                $request->header('x-api-secret') ??
                $request->header('X-API-Secret') ??
                $request->header('X-Api-Secret');

            if (!$apiKey || !$apiSecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key and secret are required (in body or headers)'
                ], 401);
            }

            // Extract domain for verification
            $domain = $request->header('Origin') ?? $validated['domain'] ?? null;

            // If no domain provided, extract from request URL as fallback
            if (!$domain) {
                $requestUrl = $request->url();
                $parsedUrl = parse_url($requestUrl);
                if (isset($parsedUrl['host'])) {
                    $domain = $parsedUrl['host'];
                }
            }

            // Verify application credentials
            $application = $this->verifyApplicationCredentials(
                $apiKey,
                $apiSecret,
                $domain
            );

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid application credentials or unauthorized domain'
                ], 401);
            }

            // Authenticate user based on current auth type
            $user = $this->authenticateUser($validated['net_id'], $validated['password'] ?? null);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user credentials'
                ], 401);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'User account is inactive'
                ], 403);
            }

            // Check if user has access to this application
            if (!$application->hasAccess($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have access to this application',
                    'user_info' => [
                        'id' => $user->id,
                        'net_id' => $user->net_id,
                        'name' => $user->name,
                    ]
                ], 403);
            }

            // Generate API token for the user
            $token = $user->createToken($application->name . ' API Access')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Authentication successful',
                'user' => [
                    'id' => $user->id,
                    'net_id' => $user->net_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'roles' => $user->roles->pluck('name')->toArray(),
                ],
                'token' => $token,
                'application' => [
                    'id' => $application->id,
                    'name' => $application->name,
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API Authentication error', [
                'error' => $e->getMessage(),
                'request' => $request->except(['password', 'api_secret'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during authentication'
            ], 500);
        }
    }

    /**
     * Verify user access to a specific application.
     */
    public function verifyAccess(Request $request): JsonResponse
    {
        try {
            // Debug logging to see what's being received
            Log::info('API verify-access request debug', [
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'raw_content' => $request->getContent(),
                'all_input' => $request->all(),
                'has_net_id' => $request->has('net_id'),
                'net_id_value' => $request->input('net_id'),
            ]);

            $validated = $request->validate([
                'api_key' => 'sometimes|string',
                'api_secret' => 'sometimes|string',
                'user_id' => 'sometimes|integer',
                'net_id' => 'sometimes|string',
                'domain' => 'sometimes|string|nullable',
            ], [
                'required_without' => 'Either user_id or net_id is required'
            ]);

            // Validate that at least one identifier is provided
            if (!isset($validated['user_id']) && !isset($validated['net_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either user_id or net_id is required'
                ], 422);
            }

            // Get API credentials from headers if not in body (support multiple header formats)
            $apiKey = $validated['api_key'] ??
                $request->header('x-api-key') ??
                $request->header('X-API-Key') ??
                $request->header('X-Api-Key');

            $apiSecret = $validated['api_secret'] ??
                $request->header('x-api-secret') ??
                $request->header('X-API-Secret') ??
                $request->header('X-Api-Secret');

            if (!$apiKey || !$apiSecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key and secret are required (in body or headers)'
                ], 401);
            }

            // Extract domain for verification
            $domain = $request->header('Origin') ?? $validated['domain'] ?? null;

            // If no domain provided, extract from request URL as fallback
            if (!$domain) {
                $requestUrl = $request->url();
                $parsedUrl = parse_url($requestUrl);
                if (isset($parsedUrl['host'])) {
                    $domain = $parsedUrl['host'];
                }
            }

            // Verify application credentials
            $application = $this->verifyApplicationCredentials(
                $apiKey,
                $apiSecret,
                $domain
            );

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid application credentials or unauthorized domain'
                ], 401);
            }

            // Find user by ID or net_id
            $user = null;
            if (isset($validated['user_id'])) {
                $user = User::find($validated['user_id']);
            } else {
                $user = User::where('net_id', $validated['net_id'])->first();
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $hasAccess = $application->hasAccess($user);

            return response()->json([
                'success' => true,
                'has_access' => $hasAccess,
                'user' => [
                    'id' => $user->id,
                    'net_id' => $user->net_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                ],
                'application' => [
                    'id' => $application->id,
                    'name' => $application->name,
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API Access verification error', [
                'error' => $e->getMessage(),
                'request' => $request->except(['api_secret'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during access verification'
            ], 500);
        }
    }

    /**
     * Get user information.
     */
    public function getUserInfo(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'api_key' => 'sometimes|string',
                'api_secret' => 'sometimes|string',
                'user_id' => 'sometimes|integer',
                'net_id' => 'sometimes|string',
            ]);

            // Get API credentials from headers if not in body (support multiple header formats)
            $apiKey = $validated['api_key'] ??
                $request->header('x-api-key') ??
                $request->header('X-API-Key') ??
                $request->header('X-Api-Key');

            $apiSecret = $validated['api_secret'] ??
                $request->header('x-api-secret') ??
                $request->header('X-API-Secret') ??
                $request->header('X-Api-Secret');

            if (!$apiKey || !$apiSecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key and secret are required (in body or headers)'
                ], 401);
            }

            // Verify application credentials
            $application = $this->verifyApplicationCredentials(
                $apiKey,
                $apiSecret
            );

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid application credentials'
                ], 401);
            }

            // Find user
            $user = null;
            if (isset($validated['user_id'])) {
                $user = User::find($validated['user_id']);
            } elseif (isset($validated['net_id'])) {
                $user = User::where('net_id', $validated['net_id'])->first();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Either user_id or net_id is required'
                ], 422);
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'net_id' => $user->net_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'description' => $role->description,
                        ];
                    })->toArray(),
                    'applications' => $user->roles()
                        ->with('applications')
                        ->get()
                        ->pluck('applications')
                        ->flatten()
                        ->unique('id')
                        ->map(function ($app) {
                            return [
                                'id' => $app->id,
                                'name' => $app->name,
                                'description' => $app->description,
                            ];
                        })
                        ->values()
                        ->toArray(),
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API Get User Info error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving user information'
            ], 500);
        }
    }

    /**
     * Check if user has a specific role.
     */
    public function checkRole(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'api_key' => 'sometimes|string',
                'api_secret' => 'sometimes|string',
                'user_id' => 'sometimes|integer',
                'net_id' => 'sometimes|string',
                'role_name' => 'required|string',
            ]);

            // Get API credentials from headers if not in body (support multiple header formats)
            $apiKey = $validated['api_key'] ??
                $request->header('x-api-key') ??
                $request->header('X-API-Key') ??
                $request->header('X-Api-Key');

            $apiSecret = $validated['api_secret'] ??
                $request->header('x-api-secret') ??
                $request->header('X-API-Secret') ??
                $request->header('X-Api-Secret');

            if (!$apiKey || !$apiSecret) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key and secret are required (in body or headers)'
                ], 401);
            }

            // Verify application credentials
            $application = $this->verifyApplicationCredentials(
                $apiKey,
                $apiSecret
            );

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid application credentials'
                ], 401);
            }

            // Find user
            $user = null;
            if (isset($validated['user_id'])) {
                $user = User::find($validated['user_id']);
            } elseif (isset($validated['net_id'])) {
                $user = User::where('net_id', $validated['net_id'])->first();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Either user_id or net_id is required'
                ], 422);
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $hasRole = $user->roles()->where('name', $validated['role_name'])->exists();

            return response()->json([
                'success' => true,
                'has_role' => $hasRole,
                'user' => [
                    'id' => $user->id,
                    'net_id' => $user->net_id,
                    'name' => $user->name,
                ],
                'role_name' => $validated['role_name'],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API Check Role error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking user role'
            ], 500);
        }
    }

    /**
     * Health check endpoint.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'API is healthy',
            'timestamp' => now()->toISOString(),
            'auth_type' => AuthenticationService::getType(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * Verify application API credentials.
     */
    private function verifyApplicationCredentials(string $apiKey, string $apiSecret, ?string $domain = null): ?Application
    {
        $application = Application::where('api_key', $apiKey)->first();

        if (!$application) {
            return null;
        }

        if (!$application->verifyApiCredentials($apiKey, $apiSecret)) {
            return null;
        }

        if (!$application->isDomainAllowed($domain)) {
            return null;
        }

        return $application;
    }

    /**
     * Authenticate user based on current authentication type.
     * For API requests, external applications are trusted to have already verified the user.
     */
    private function authenticateUser(string $netId, ?string $password = null): ?User
    {
        // For API authentication, if no password is provided, trust that the external
        // application has already authenticated the user (since API credentials were verified)
        if (!$password) {
            return User::where('net_id', $netId)->first();
        }

        // If password is provided, verify it regardless of auth type
        $user = User::where('net_id', $netId)
            ->orWhere('email', $netId)
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        return null;
    }

    /**
     * Complete pending external authorization after CAS authentication.
     */
    private function completeExternalAuthorization(?string $requestId)
    {
        $pending = session('external_auth.pending');

        if (!$pending || !is_array($pending)) {
            Log::warning('External auth callback rejected: no pending session', [
                'request_id' => $requestId,
            ]);
            return response()->json([
                'success' => false,
                'error' => 'invalid_request',
                'message' => 'No pending external authorization request found',
            ], 400);
        }

        if (empty($pending['request_id']) || $pending['request_id'] !== $requestId) {
            Log::warning('External auth callback rejected: request mismatch', [
                'request_id' => $requestId,
                'pending_request_id' => $pending['request_id'] ?? null,
            ]);
            return $this->redirectExternalError(
                $pending['redirect_uri'] ?? null,
                $pending['state'] ?? null,
                'invalid_request',
                'Authorization request does not match active session'
            );
        }

        if (($pending['expires_at'] ?? 0) < now()->timestamp) {
            session()->forget('external_auth.pending');
            Log::warning('External auth callback rejected: pending request expired', [
                'request_id' => $requestId,
                'application_id' => $pending['application_id'] ?? null,
            ]);
            return $this->redirectExternalError(
                $pending['redirect_uri'] ?? null,
                $pending['state'] ?? null,
                'invalid_request',
                'Authorization request has expired'
            );
        }

        $application = Application::find($pending['application_id'] ?? 0);
        if (!$application || !$application->canUseExternalAuthBroker()) {
            session()->forget('external_auth.pending');
            Log::warning('External auth callback rejected: invalid client in pending session', [
                'request_id' => $requestId,
                'application_id' => $pending['application_id'] ?? null,
            ]);
            return $this->redirectExternalError(
                $pending['redirect_uri'] ?? null,
                $pending['state'] ?? null,
                'invalid_client',
                'Client is not allowed to use external authentication'
            );
        }

        if (!function_exists('cas') || !cas()->checkAuthentication()) {
            Log::warning('External auth callback rejected: CAS authentication incomplete', [
                'request_id' => $requestId,
                'application_id' => $application->id,
            ]);
            return $this->redirectExternalError(
                $pending['redirect_uri'],
                $pending['state'],
                'login_required',
                'CAS authentication was not completed'
            );
        }

        $netId = cas()->user();
        $user = $netId ? User::where('net_id', $netId)->first() : null;

        if (!$user || !$user->is_active) {
            session()->forget('external_auth.pending');
            Log::warning('External auth callback rejected: user missing or inactive', [
                'request_id' => $requestId,
                'application_id' => $application->id,
                'net_id' => $netId,
            ]);
            return $this->redirectExternalError(
                $pending['redirect_uri'],
                $pending['state'],
                'unauthorized_user',
                'User account is missing or inactive'
            );
        }

        if (!$application->hasAccess($user)) {
            session()->forget('external_auth.pending');
            Log::warning('External auth callback rejected: access denied', [
                'request_id' => $requestId,
                'application_id' => $application->id,
                'user_id' => $user->id,
            ]);
            return $this->redirectExternalError(
                $pending['redirect_uri'],
                $pending['state'],
                'access_denied',
                'User does not have access to this application'
            );
        }

        $code = bin2hex(random_bytes(32));
        ExternalAuthorizationCode::create([
            'application_id' => $application->id,
            'user_id' => $user->id,
            'code_hash' => hash('sha256', $code),
            'redirect_uri' => $pending['redirect_uri'],
            'state' => $pending['state'] ?? null,
            'expires_at' => now()->addMinutes((int) config('auth.external_broker.code_ttl_minutes', 5)),
        ]);

        Log::info('External auth code issued', [
            'request_id' => $requestId,
            'application_id' => $application->id,
            'user_id' => $user->id,
        ]);

        session()->forget('external_auth.pending');

        return redirect()->away($this->buildRedirectUri($pending['redirect_uri'], [
            'code' => $code,
            'state' => $pending['state'] ?? null,
        ]));
    }

    /**
     * Redirect back to external callback URL with standardized error params.
     */
    private function redirectExternalError(?string $redirectUri, ?string $state, string $error, string $description)
    {
        if (!$redirectUri) {
            return response()->json([
                'success' => false,
                'error' => $error,
                'message' => $description,
            ], 400);
        }

        return redirect()->away($this->buildRedirectUri($redirectUri, [
            'error' => $error,
            'error_description' => $description,
            'state' => $state,
        ]));
    }

    /**
     * Build redirect URI preserving existing query parameters.
     */
    private function buildRedirectUri(string $baseUri, array $params): string
    {
        $parts = parse_url($baseUri);
        $existing = [];

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $existing);
        }

        $query = http_build_query(array_filter(array_merge($existing, $params), fn($value) => $value !== null));

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $scheme . '://' . $host . $port . $path . ($query ? '?' . $query : '') . $fragment;
    }

    /**
     * Enforce HTTPS callback URIs outside local development.
     */
    private function isAllowedRedirectScheme(string $redirectUri): bool
    {
        $requireHttps = (bool) config('auth.external_broker.require_https_redirects', true);
        if (!$requireHttps || app()->environment('local', 'testing')) {
            return true;
        }

        return strtolower((string) parse_url($redirectUri, PHP_URL_SCHEME)) === 'https';
    }
}
