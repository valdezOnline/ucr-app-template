<?php

// Test full authentication flow
require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Authentication Flow Test ===\n\n";

// Test 1: Check current configuration
echo "1. Configuration Check:\n";
echo "   AUTH_TYPE: " . config('auth.type') . "\n";
echo "   CAS_MASQUERADE: " . config('cas.cas_masquerade') . "\n";
echo "   isCasAuth(): " . (\App\Services\AuthenticationService::isCasAuth() ? 'true' : 'false') . "\n\n";

// Test 2: Simulate CAS login request
echo "2. Simulating CAS Login Request:\n";
$loginRequest = Request::create('/cas/login', 'GET');

try {
    $loginResponse = $kernel->handle($loginRequest);
    echo "   Status: " . $loginResponse->getStatusCode() . "\n";

    if ($loginResponse->isRedirection()) {
        echo "   Redirect to: " . $loginResponse->headers->get('Location') . "\n";
    }

    $kernel->terminate($loginRequest, $loginResponse);
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Check if user would be authenticated after CAS login
echo "3. Manual Authentication Test:\n";
if (config('cas.cas_masquerade')) {
    $netId = config('cas.cas_masquerade');
    $user = \App\Models\User::where('net_id', $netId)->first();

    if ($user) {
        echo "   Found user: {$user->net_id}\n";
        echo "   Is active: " . ($user->is_active ? 'true' : 'false') . "\n";
        echo "   Is admin: " . ($user->is_admin ? 'true' : 'false') . "\n";

        // Simulate login
        Auth::login($user);
        echo "   Auth::login() called\n";
        echo "   Auth::check(): " . (Auth::check() ? 'true' : 'false') . "\n";
        echo "   Auth::user(): " . (Auth::user() ? Auth::user()->net_id : 'null') . "\n";
    } else {
        echo "   User not found\n";
    }
}

echo "\n";

// Test 4: Try accessing protected route
echo "4. Protected Route Access Test:\n";
$protectedRequest = Request::create('/admin/auth-config', 'GET');

// Set up session with authenticated user
if (Auth::check()) {
    $session = app('session');
    $session->put('login_' . Auth::getDefaultDriver() . '_' . sha1(Auth::class), Auth::id());
    $protectedRequest->setLaravelSession($session);
}

try {
    $protectedResponse = $kernel->handle($protectedRequest);
    echo "   Status: " . $protectedResponse->getStatusCode() . "\n";

    if ($protectedResponse->isRedirection()) {
        echo "   Redirect to: " . $protectedResponse->headers->get('Location') . "\n";
    } else {
        echo "   Response: Success (no redirect)\n";
    }

    $kernel->terminate($protectedRequest, $protectedResponse);
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";