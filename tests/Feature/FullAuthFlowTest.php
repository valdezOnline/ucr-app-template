<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cas_masquerade_login_authenticates_and_can_access_admin_auth_config(): void
    {
        config()->set('auth.type', 'cas');

        $admin = User::factory()->create([
            'net_id' => 'cas-admin',
            'is_admin' => true,
            'is_active' => true,
        ]);

        config()->set('cas.cas_masquerade', $admin->net_id);

        $loginResponse = $this->get('/cas/login');
        $loginResponse->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);

        $configResponse = $this->get('/admin/auth-config');
        $configResponse->assertOk();
    }
}