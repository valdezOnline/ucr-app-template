<?php

// Test auth config functionality
require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Auth Config Test ===\n\n";

// Test 1: Current configuration
echo "1. Current Configuration:\n";
echo "   AUTH_TYPE: " . config('auth.type') . "\n";
echo "   AuthenticationService::getType(): " . \App\Services\AuthenticationService::getType() . "\n\n";

// Test 2: Authenticate a user for testing
echo "2. Authenticating test user:\n";
$user = \App\Models\User::where('net_id', 'joelval')->first();
if ($user) {
    Auth::login($user);
    echo "   User authenticated: " . $user->net_id . "\n";
    echo "   Auth::check(): " . (Auth::check() ? 'true' : 'false') . "\n\n";
} else {
    echo "   User not found\n\n";
}

// Test 3: Test auth-config route access
echo "3. Testing auth-config access:\n";
$request = Request::create('/admin/auth-config', 'GET');

// Set up proper session
$session = app('session.store');
if (Auth::check()) {
    $session->put('login_web_' . sha1('Illuminate\Auth\SessionGuard'), Auth::id());
}
$request->setLaravelSession($session);

try {
    $response = $kernel->handle($request);
    echo "   Status: " . $response->getStatusCode() . "\n";

    if ($response->isRedirection()) {
        echo "   Redirect to: " . $response->headers->get('Location') . "\n";
    } else {
        echo "   Success: Page loaded successfully\n";
    }

    $kernel->terminate($request, $response);
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";