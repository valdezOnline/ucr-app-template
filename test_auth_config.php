<?php

// Test auth config route
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create session for testing
$session = new Illuminate\Session\Store(
    'app_session',
    new Illuminate\Session\ArraySessionHandler(120),
    'test_session'
);

// Create a test request to /admin/auth-config
$request = Illuminate\Http\Request::create('/admin/auth-config', 'GET');
$request->setLaravelSession($session);

try {
    $response = $kernel->handle($request);
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Headers:\n";
    foreach ($response->headers->all() as $name => $values) {
        foreach ($values as $value) {
            echo "  {$name}: {$value}\n";
        }
    }

    if ($response->getStatusCode() === 500) {
        echo "\nResponse Content (first 1000 chars):\n";
        echo substr($response->getContent(), 0, 1000) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response ?? null);