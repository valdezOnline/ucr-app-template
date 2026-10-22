<?php

namespace Tests\Feature;

use App\Services\AuthenticationService;
use Tests\TestCase;

class AuthServiceSmokeTest extends TestCase
{
    public function test_authentication_service_and_cas_helper_are_available(): void
    {
        config()->set('auth.type', 'cas');
        config()->set('cas.cas_hostname', 'sso.ucr.edu');

        $this->assertSame('cas', AuthenticationService::getType());
        $this->assertTrue(AuthenticationService::isCasAuth());
        $this->assertFalse(AuthenticationService::isLaravelAuth());
        $this->assertTrue(AuthenticationService::isCasAvailable());

        $this->assertTrue(function_exists('cas'));
        $this->assertIsObject(cas());
    }
}