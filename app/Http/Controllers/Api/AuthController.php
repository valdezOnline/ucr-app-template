<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Services\AuthenticationService;
// use App\Services\CasService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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
}
