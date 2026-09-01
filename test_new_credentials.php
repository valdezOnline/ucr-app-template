<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test the NEW API credentials
$apiKey = 'app_1f47901f3ed21dfae3f0342da4b32bae75b85d8f8c746d7a';
$apiSecret = '37fb56d4c8107e50274efd71f7bb77a9507e01f8e32f3699227000bdf570a5a5';

echo "Testing NEW API credentials...\n";
echo "API Key: $apiKey\n";
echo "API Secret: " . substr($apiSecret, 0, 10) . "...\n\n";

// Find application
$application = App\Models\Application::where('api_key', $apiKey)->first();

if (!$application) {
    echo "❌ Application not found with new API key\n";
    exit(1);
}

echo "✅ Application found:\n";
echo "  ID: {$application->id}\n";
echo "  Name: {$application->name}\n";
echo "  API Active: " . ($application->api_active ? 'Yes' : 'No') . "\n";
echo "  Supports API Auth: " . ($application->supports_api_auth ? 'Yes' : 'No') . "\n";
echo "  Is Active: " . ($application->is_active ? 'Yes' : 'No') . "\n";
echo "  API Expires: " . ($application->api_key_expires_at ? $application->api_key_expires_at->format('Y-m-d H:i:s') : 'Never') . "\n";
echo "  Allowed Domains: " . json_encode($application->allowed_domains) . "\n\n";

// Test credential verification using the model method
echo "Testing credential verification...\n";
$isValid = $application->verifyApiCredentials($apiKey, $apiSecret);
echo "Credentials valid: " . ($isValid ? '✅ Yes' : '❌ No') . "\n\n";

// Test domain verification for localhost
echo "Testing domain verification for localhost...\n";
$domainAllowed = $application->isDomainAllowed('localhost');
echo "localhost allowed: " . ($domainAllowed ? '✅ Yes' : '❌ No') . "\n";

$domainAllowed = $application->isDomainAllowed(null);
echo "No domain (null) allowed: " . ($domainAllowed ? '✅ Yes' : '❌ No') . "\n\n";

// Test the complete verification flow
echo "Testing complete verification flow...\n";
if ($isValid && $application->isDomainAllowed('localhost')) {
    echo "🎉 All verification passed! The API should work.\n";
} else {
    echo "❌ Verification failed somewhere.\n";
}

echo "\n--- Testing OLD credentials (should fail) ---\n";
$oldApiKey = 'app_bcd2113f6014766964c3296a375fbb97f3ed7ad400c329df';
$oldApiSecret = '15b23ebeb577e2a9f257334152011b749725a81dcfb6c96becf4f60d75d770ec';

$oldApp = App\Models\Application::where('api_key', $oldApiKey)->first();
if ($oldApp) {
    echo "❌ OLD app still exists with old API key\n";
} else {
    echo "✅ OLD API key no longer exists (good)\n";
}