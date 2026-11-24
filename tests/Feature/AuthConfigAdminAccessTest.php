<?php

namespace Tests\Feature;

use Modules\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthConfigAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_auth_config_page(): void
    {
        $admin = User::factory()->admin()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/auth-config');

        $response->assertOk();
    }
}