<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthConfigGuestRedirectTest extends TestCase
{
    public function test_guest_is_redirected_from_admin_auth_config(): void
    {
        $response = $this->get('/admin/auth-config');

        $response->assertRedirect(route('login'));
    }
}