<?php

namespace Tests\Feature;

use Modules\Core\Application\Auth\AuthenticationManager;
use Tests\TestCase;

class AuthServiceSmokeTest extends TestCase
{
    public function test_authentication_manager_and_cas_helper_are_available(): void
    {
        config()->set('auth.type', 'cas');
        config()->set('cas.cas_hostname', 'sso.ucr.edu');
        $authenticationManager = app(AuthenticationManager::class);

        $this->assertSame('cas', $authenticationManager->getType());
        $this->assertTrue($authenticationManager->isCasAuth());
        $this->assertFalse($authenticationManager->isLaravelAuth());
        $this->assertTrue($authenticationManager->isCasAvailable());

        $this->assertTrue(function_exists('cas'));
        $this->assertIsObject(cas());
    }
}