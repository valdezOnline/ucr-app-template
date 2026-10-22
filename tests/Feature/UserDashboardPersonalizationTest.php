<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDashboardPersonalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_application_order_for_visible_applications(): void
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'staff',
            'display_name' => 'Staff',
            'is_active' => true,
        ]);

        $applications = collect([
            Application::create(['name' => 'One', 'url' => 'https://one.example.com', 'is_active' => true]),
            Application::create(['name' => 'Two', 'url' => 'https://two.example.com', 'is_active' => true]),
            Application::create(['name' => 'Three', 'url' => 'https://three.example.com', 'is_active' => true]),
        ]);

        $user->roles()->attach($role->id);
        $role->applications()->attach($applications->pluck('id')->all());

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->postJson(route('dashboard.applications.order'), [
                'application_ids' => [
                    $applications[2]->id,
                    $applications[0]->id,
                ],
            ], ['X-CSRF-TOKEN' => 'test-token'])
            ->assertOk()
            ->assertJson([
                'message' => 'Application order saved.',
            ]);

        $user->refresh();
        $this->assertSame(
            [
                $applications[2]->id,
                $applications[0]->id,
                $applications[1]->id,
            ],
            $user->dashboard_application_order
        );
    }

    public function test_user_cannot_save_application_order_with_inaccessible_application(): void
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'staff',
            'display_name' => 'Staff',
            'is_active' => true,
        ]);

        $visibleApp = Application::create(['name' => 'Visible', 'url' => 'https://visible.example.com', 'is_active' => true]);
        $hiddenApp = Application::create(['name' => 'Hidden', 'url' => 'https://hidden.example.com', 'is_active' => true]);

        $user->roles()->attach($role->id);
        $role->applications()->attach($visibleApp->id);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->postJson(route('dashboard.applications.order'), [
                'application_ids' => [
                    $hiddenApp->id,
                    $visibleApp->id,
                ],
            ], ['X-CSRF-TOKEN' => 'test-token'])
            ->assertStatus(422);

        $this->assertNull($user->refresh()->dashboard_application_order);
    }

    public function test_user_can_add_internal_shortcut_with_category(): void
    {
        $user = User::factory()->create();

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->post(route('dashboard.shortcuts.store'), [
                'name' => 'My Profile',
                'type' => 'internal',
                'url' => 'profile',
                'category' => 'Daily',
                '_token' => 'test-token',
            ])
            ->assertRedirect();

        $shortcuts = $user->refresh()->dashboard_shortcuts;

        $this->assertCount(1, $shortcuts);
        $this->assertSame('My Profile', $shortcuts[0]['name']);
        $this->assertSame('internal', $shortcuts[0]['type']);
        $this->assertSame('/profile', $shortcuts[0]['url']);
        $this->assertSame('Daily', $shortcuts[0]['category']);
        $this->assertNotEmpty($shortcuts[0]['id']);
    }

    public function test_user_can_update_shortcut(): void
    {
        $user = User::factory()->create([
            'dashboard_shortcuts' => [
                ['id' => 'abc-1', 'name' => 'Old Name', 'type' => 'internal', 'url' => '/old', 'category' => 'Daily'],
            ],
        ]);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->put(route('dashboard.shortcuts.update', 'abc-1'), [
                '_token' => 'test-token',
                'name' => 'Updated Name',
                'type' => 'external',
                'url' => 'https://example.com/new',
                'category' => 'daily',
            ])
            ->assertRedirect();

        $updated = $user->refresh()->dashboard_shortcuts;
        $this->assertCount(1, $updated);
        $this->assertSame('Updated Name', $updated[0]['name']);
        $this->assertSame('external', $updated[0]['type']);
        $this->assertSame('https://example.com/new', $updated[0]['url']);
        $this->assertSame('Daily', $updated[0]['category']);
    }

    public function test_user_can_remove_shortcut(): void
    {
        $user = User::factory()->create([
            'dashboard_shortcuts' => [
                ['id' => 'abc-1', 'name' => 'One', 'type' => 'internal', 'url' => '/home', 'category' => 'A'],
                ['id' => 'abc-2', 'name' => 'Two', 'type' => 'external', 'url' => 'https://example.com', 'category' => null],
            ],
        ]);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->delete(route('dashboard.shortcuts.destroy', 'abc-1'), ['_token' => 'test-token'])
            ->assertRedirect();

        $remaining = $user->refresh()->dashboard_shortcuts;
        $this->assertCount(1, $remaining);
        $this->assertSame('abc-2', $remaining[0]['id']);
    }
}
