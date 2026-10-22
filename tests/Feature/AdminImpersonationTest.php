<?php

namespace Tests\Feature;

use App\Models\ImpersonationAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_impersonate_non_admin_user_and_stop_impersonation(): void
    {
        config()->set('auth.type', 'laravel');

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $startResponse = $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.impersonation.start', $target), ['_token' => 'test-token']);

        $startResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($target);
        $this->assertTrue(session('impersonation.active'));

        $audit = ImpersonationAudit::first();
        $this->assertNotNull($audit);
        $this->assertEquals($admin->id, $audit->impersonator_user_id);
        $this->assertEquals($target->id, $audit->impersonated_user_id);
        $this->assertNull($audit->ended_at);

        $stopResponse = $this->withSession(['_token' => 'test-token'])
            ->post(route('admin.impersonation.stop'), ['_token' => 'test-token']);

        $stopResponse->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);
        $this->assertFalse(session()->has('impersonation'));

        $audit->refresh();
        $this->assertNotNull($audit->ended_at);
        $this->assertEquals('manual', $audit->stop_reason);
    }

    public function test_admin_cannot_impersonate_admin_user(): void
    {
        config()->set('auth.type', 'laravel');

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);
        $otherAdmin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('users.index'))
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.impersonation.start', $otherAdmin), ['_token' => 'test-token']);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('error', 'Administrators cannot impersonate other administrators.');
        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseCount('impersonation_audits', 0);
    }

    public function test_logout_stops_impersonation_and_restores_admin_for_laravel_auth(): void
    {
        config()->set('auth.type', 'laravel');

        $admin = User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'is_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.impersonation.start', $target), ['_token' => 'test-token']);
        $this->assertAuthenticatedAs($target);

        $logoutResponse = $this->withSession(['_token' => 'test-token'])
            ->post(route('logout'), ['_token' => 'test-token']);

        $logoutResponse->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);
        $this->assertFalse(session()->has('impersonation'));

        $audit = ImpersonationAudit::first();
        $this->assertNotNull($audit);
        $this->assertNotNull($audit->ended_at);
        $this->assertEquals('logout', $audit->stop_reason);
    }

    public function test_cas_logout_stops_impersonation_and_restores_admin_session(): void
    {
        config()->set('auth.type', 'cas');

        $admin = User::factory()->create([
            'net_id' => 'cas-admin',
            'is_admin' => true,
            'is_active' => true,
        ]);
        $target = User::factory()->create([
            'net_id' => 'cas-target',
            'is_admin' => false,
            'is_active' => true,
        ]);

        $audit = ImpersonationAudit::create([
            'impersonator_user_id' => $admin->id,
            'impersonated_user_id' => $target->id,
            'auth_type' => 'cas',
            'started_at' => now(),
            'start_ip' => '127.0.0.1',
            'start_user_agent' => 'phpunit',
        ]);

        $response = $this->actingAs($target)->withSession([
            'cas_user' => $target->net_id,
            'impersonation' => [
                'active' => true,
                'impersonator_id' => $admin->id,
                'impersonator_name' => $admin->full_name,
                'impersonator_net_id' => $admin->net_id,
                'impersonated_user_id' => $target->id,
                'audit_id' => $audit->id,
                'original_cas_user' => $admin->net_id,
            ],
        ])->get(route('cas.logout'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);
        $this->assertEquals($admin->net_id, session('cas_user'));
        $this->assertFalse(session()->has('impersonation'));

        $audit->refresh();
        $this->assertNotNull($audit->ended_at);
        $this->assertEquals('logout', $audit->stop_reason);
    }
}
