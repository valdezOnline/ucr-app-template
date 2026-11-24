<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Tests\TestCase;

class AdminDashboardOnlineUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_current_online_users_section_title(): void
    {
        $admin = User::factory()->admin()->create([
            'is_active' => true,
            'last_seen_at' => null,
        ]);

        User::factory()->create([
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Current Online Users');
        $response->assertDontSee('Current Active Users');
    }

    public function test_current_online_users_defaults_to_ten_records_per_page(): void
    {
        $admin = User::factory()->admin()->create([
            'is_active' => true,
            'last_seen_at' => null,
        ]);

        User::factory()->count(23)->create([
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('home'));
        $onlineUsers = $response->viewData('onlineUsers');

        $response->assertOk();
        $response->assertViewHas('onlineUsersPerPage', 10);
        $this->assertSame(10, $onlineUsers->perPage());
        $this->assertSame(24, $onlineUsers->total());
        $response->assertSee('name="online_users_per_page"', false);
    }

    public function test_current_online_users_supports_custom_records_per_page(): void
    {
        $admin = User::factory()->admin()->create([
            'is_active' => true,
            'last_seen_at' => null,
        ]);

        User::factory()->count(23)->create([
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('home', ['online_users_per_page' => 25]));
        $onlineUsers = $response->viewData('onlineUsers');

        $response->assertOk();
        $response->assertViewHas('onlineUsersPerPage', 25);
        $this->assertSame(25, $onlineUsers->perPage());
        $this->assertSame(24, $onlineUsers->total());
    }
}
