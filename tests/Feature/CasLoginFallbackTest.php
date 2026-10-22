<?php

namespace Tests\Feature;

use Tests\TestCase;

class CasLoginFallbackTest extends TestCase
{
    public function test_cas_login_route_redirects_to_local_login_when_cas_mode_is_disabled(): void
    {
        config()->set('auth.type', 'laravel');

        $response = $this->get('/cas/login');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('info');
    }
}