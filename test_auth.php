<?php

// Test authentication configuration
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Authentication Configuration:\n";
echo "====================================\n";

echo "AUTH_TYPE: " . config('auth.type') . "\n";
echo "CAS_MASQUERADE: " . config('cas.cas_masquerade') . "\n";
echo "CAS_HOSTNAME: " . config('cas.cas_hostname') . "\n";

echo "\nAuthenticationService Tests:\n";
echo "---------------------------\n";
echo "getType(): " . \App\Services\AuthenticationService::getType() . "\n";
echo "isCasAuth(): " . (\App\Services\AuthenticationService::isCasAuth() ? 'true' : 'false') . "\n";
echo "isLaravelAuth(): " . (\App\Services\AuthenticationService::isLaravelAuth() ? 'true' : 'false') . "\n";
echo "isCasAvailable(): " . (\App\Services\AuthenticationService::isCasAvailable() ? 'true' : 'false') . "\n";

echo "\nCAS Function Test:\n";
echo "-----------------\n";
echo "function_exists('cas'): " . (function_exists('cas') ? 'true' : 'false') . "\n";

if (function_exists('cas')) {
    try {
        $casInstance = cas();
        echo "CAS instance created successfully\n";

        // Test masquerade mode
        if (config('cas.cas_masquerade')) {
            echo "Masquerade mode detected\n";
            echo "Masquerade user: " . config('cas.cas_masquerade') . "\n";
        }
    } catch (Exception $e) {
        echo "CAS instance error: " . $e->getMessage() . "\n";
    }
}

echo "\nUser Test:\n";
echo "---------\n";
$user = \App\Models\User::where('net_id', 'joelval')->first();
if ($user) {
    echo "User found: " . $user->net_id . "\n";
    echo "Is active: " . ($user->is_active ? 'true' : 'false') . "\n";
    echo "Is admin: " . ($user->is_admin ? 'true' : 'false') . "\n";
} else {
    echo "User not found\n";
}

echo "\nTest completed.\n";