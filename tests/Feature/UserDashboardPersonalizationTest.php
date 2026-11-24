<?php

namespace Tests\Feature;

use Modules\Core\Models\Application;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
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

    public function test_user_can_set_shortcut_category_group_style_when_creating_shortcut(): void
    {
        $user = User::factory()->create(['dashboard_shortcut_group_display_mode' => 'list']);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->post(route('dashboard.shortcuts.store'), [
                'name' => 'Reports',
                'type' => 'internal',
                'url' => '/reports',
                'category' => 'Daily',
                'shortcut_group_display_mode' => 'nav-pills',
                '_token' => 'test-token',
            ])
            ->assertRedirect();

        $this->assertSame('nav-pills', $user->refresh()->dashboard_shortcut_group_display_mode);
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

    public function test_user_can_update_display_mode(): void
    {
        $user = User::factory()->create(['dashboard_display_mode' => 'normal']);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->postJson(route('dashboard.display-mode.update'), [
                'display_mode' => 'small',
            ], ['X-CSRF-TOKEN' => 'test-token'])
            ->assertOk()
            ->assertJson([
                'message' => 'Display mode updated successfully.',
                'display_mode' => 'small',
            ]);

        $this->assertSame('small', $user->refresh()->dashboard_display_mode);
    }

    public function test_user_can_update_dashboard_width_preference(): void
    {
        $user = User::factory()->create(['dashboard_full_width' => false]);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->postJson(route('dashboard.full-width.update'), [
                'full_width' => true,
            ], ['X-CSRF-TOKEN' => 'test-token'])
            ->assertOk()
            ->assertJson([
                'message' => 'Application width updated successfully.',
                'full_width' => true,
            ]);

        $this->assertTrue($user->refresh()->dashboard_full_width);
    }

    public function test_dashboard_width_preference_controls_the_main_layout(): void
    {
        $containerUser = User::factory()->create(['dashboard_full_width' => false]);

        $this->actingAs($containerUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('dashboardFullWidth', false)
            ->assertSee('<main class="py-4">', false)
            ->assertSee('<div class="container">', false)
            ->assertSee('id="dashboardWidthContainer" value="container"', false);

        $fullWidthUser = User::factory()->create(['dashboard_full_width' => true]);

        $this->actingAs($fullWidthUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('dashboardFullWidth', true)
            ->assertSee('<div class="px-3 px-md-4">', false)
            ->assertSee('id="dashboardWidthFull" value="full"', false);
    }

    public function test_profile_displays_dashboard_width_preference(): void
    {
        $user = User::factory()->create(['dashboard_full_width' => true]);

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Dashboard Width')
            ->assertSee('Full width');
    }

    public function test_user_can_save_application_groups(): void
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'staff',
            'display_name' => 'Staff',
            'is_active' => true,
        ]);

        $applications = collect([
            Application::create(['name' => 'HR App', 'url' => 'https://hr.example.com', 'is_active' => true]),
            Application::create(['name' => 'Dev App', 'url' => 'https://dev.example.com', 'is_active' => true]),
            Application::create(['name' => 'Finance App', 'url' => 'https://finance.example.com', 'is_active' => true]),
        ]);

        $user->roles()->attach($role->id);
        $role->applications()->attach($applications->pluck('id')->all());

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->postJson(route('dashboard.applications.groups.save'), [
                'groups' => [
                    [
                        'id' => 'group-1',
                        'name' => 'HR Tools',
                        'application_ids' => [$applications[0]->id],
                    ],
                    [
                        'id' => 'group-2',
                        'name' => 'Development',
                        'application_ids' => [$applications[1]->id],
                    ],
                ],
                'group_display_mode' => 'tabs',
            ], ['X-CSRF-TOKEN' => 'test-token'])
            ->assertOk()
            ->assertJson([
                'message' => 'Application groups saved successfully.',
            ]);

        $user->refresh();
        $groups = $user->dashboard_groups;
        $this->assertCount(2, $groups);
        $this->assertSame('HR Tools', $groups[0]['name']);
        $this->assertSame('Development', $groups[1]['name']);
        $this->assertSame('tabs', $user->dashboard_group_display_mode);
    }

    public function test_user_can_delete_all_application_groups(): void
    {
        $user = User::factory()->create([
            'dashboard_groups' => [
                ['id' => 'group-1', 'name' => 'Work Tools', 'application_ids' => [1]],
            ],
        ]);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->postJson(route('dashboard.applications.groups.save'), [
                'groups' => [],
            ], ['X-CSRF-TOKEN' => 'test-token'])
            ->assertOk();

        $this->assertSame([], $user->refresh()->dashboard_groups);
    }

    public function test_user_cannot_save_groups_with_inaccessible_applications(): void
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
            ->postJson(route('dashboard.applications.groups.save'), [
                'groups' => [
                    [
                        'id' => 'group-1',
                        'name' => 'All Apps',
                        'application_ids' => [$visibleApp->id, $hiddenApp->id],
                    ],
                ],
            ], ['X-CSRF-TOKEN' => 'test-token'])
            ->assertStatus(422)
            ->assertJson([
                'message' => 'One or more applications are not available for your account.',
            ]);
    }

    public function test_user_can_delete_application_group(): void
    {
        $user = User::factory()->create([
            'dashboard_groups' => [
                ['id' => 'group-1', 'name' => 'Group 1', 'application_ids' => [1, 2]],
                ['id' => 'group-2', 'name' => 'Group 2', 'application_ids' => [3]],
            ],
        ]);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->deleteJson(route('dashboard.applications.groups.delete', 'group-1'), [], ['X-CSRF-TOKEN' => 'test-token'])
            ->assertOk()
            ->assertJson([
                'message' => 'Group deleted successfully.',
            ]);

        $groups = $user->refresh()->dashboard_groups;
        $this->assertCount(1, $groups);
        $this->assertSame('group-2', $groups[0]['id']);
    }

    public function test_dashboard_view_shows_display_mode_and_groups(): void
    {
        $user = User::factory()->create([
            'dashboard_display_mode' => 'small',
            'dashboard_group_display_mode' => 'tabs',
            'dashboard_groups' => [
                ['id' => 'group-1', 'name' => 'Work Tools', 'application_ids' => [1]],
                ['id' => 'group-2', 'name' => 'More Tools', 'application_ids' => [2]],
            ],
        ]);
        $role = Role::create([
            'name' => 'staff',
            'display_name' => 'Staff',
            'is_active' => true,
        ]);

        $applications = collect([
            Application::create(['name' => 'Test App', 'url' => 'https://test.example.com', 'is_active' => true]),
            Application::create(['name' => 'Second App', 'url' => 'https://test2.example.com', 'is_active' => true]),
        ]);
        $user->roles()->attach($role->id);
        $role->applications()->attach($applications->pluck('id')->all());

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('displayMode', 'small');
        $response->assertViewHas('userGroups');
        $response->assertViewHas('groupedApplications');
        $response->assertSee('applications-display-small');
        $response->assertSee('application-tile-small');
        $response->assertDontSee('application-tile-normal');
        $response->assertSee('id="application-group-tabs"', false);
        $response->assertSee('id="groupDisplayModeTabs"', false);
        $response->assertSee('id="add-group-btn"', false);
        $response->assertSee('id="save-groups-btn"', false);
        $response->assertSee("const applicationGrids = Array.from(document.querySelectorAll('.application-grid'));", false);
        $response->assertSee('/dashboard/applications/groups');
        $response->assertSee('groupsContainer.appendChild(groupCard)');
    }

    public function test_dashboard_view_shows_group_accordion_when_selected(): void
    {
        $user = User::factory()->create([
            'dashboard_group_display_mode' => 'accordion',
            'dashboard_groups' => [
                ['id' => 'group-1', 'name' => 'Work Tools', 'application_ids' => [1]],
                ['id' => 'group-2', 'name' => 'More Tools', 'application_ids' => [2]],
            ],
        ]);
        $role = Role::create([
            'name' => 'staff',
            'display_name' => 'Staff',
            'is_active' => true,
        ]);

        $applications = collect([
            Application::create(['name' => 'Test App', 'url' => 'https://test.example.com', 'is_active' => true]),
            Application::create(['name' => 'Second App', 'url' => 'https://test2.example.com', 'is_active' => true]),
        ]);
        $user->roles()->attach($role->id);
        $role->applications()->attach($applications->pluck('id')->all());

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="application-groups-accordion"', false);
        $response->assertSee('id="groupDisplayModeAccordion"', false);
        $response->assertDontSee('id="application-group-tabs"', false);
    }

    public function test_dashboard_view_shows_shortcut_nav_pills_when_selected(): void
    {
        $user = User::factory()->create([
            'dashboard_shortcut_group_display_mode' => 'nav-pills',
            'dashboard_shortcuts' => [
                ['id' => 'shortcut-1', 'name' => 'Profile', 'type' => 'internal', 'url' => '/profile', 'category' => 'Daily'],
                ['id' => 'shortcut-2', 'name' => 'Help', 'type' => 'internal', 'url' => '/help', 'category' => 'Support'],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="shortcut-category-pills"', false);
        $response->assertSee('data-bs-toggle="pill"', false);
    }

    public function test_user_can_toggle_dashboard_lock(): void
    {
        $user = User::factory()->create(['dashboard_locked' => false]);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->postJson(route('dashboard.lock.toggle'), [], ['X-CSRF-TOKEN' => 'test-token'])
            ->assertOk()
            ->assertJson([
                'message' => 'Dashboard locked.',
                'locked' => true,
            ]);

        $user->refresh();
        $this->assertTrue($user->dashboard_locked);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->postJson(route('dashboard.lock.toggle'), [], ['X-CSRF-TOKEN' => 'test-token'])
            ->assertOk()
            ->assertJson([
                'message' => 'Dashboard unlocked.',
                'locked' => false,
            ]);

        $user->refresh();
        $this->assertFalse($user->dashboard_locked);
    }

    public function test_dashboard_lock_persists_across_sessions(): void
    {
        $user = User::factory()->create(['dashboard_locked' => false]);

        $this->withSession(['_token' => 'test-token'])
            ->actingAs($user)
            ->postJson(route('dashboard.lock.toggle'), [], ['X-CSRF-TOKEN' => 'test-token'])
            ->assertOk();

        $user->refresh();
        $this->assertTrue($user->dashboard_locked);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('let isDashboardLocked = true;', false);
    }
}

