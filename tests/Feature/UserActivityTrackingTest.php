<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\User;
use Modules\Core\Services\UserActivityTracker;
use Tests\TestCase;

class UserActivityTrackingTest extends TestCase
{
    public function test_user_login_logout_and_last_seen_are_recorded(): void
    {
        $user = User::factory()->create();

        Auth::login($user);

        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertNotNull($user->fresh()->last_seen_at);
        $this->assertSame('login', $user->fresh()->activities()->orderByDesc('id')->first()->event);

        $tracker = app(UserActivityTracker::class);
        $tracker->recordLastSeen($user->fresh());

        $this->assertTrue($user->fresh()->isOnline());
        $this->assertSame('last_seen', $user->fresh()->activities()->orderByDesc('id')->first()->event);

        Auth::logout();
        $tracker->recordLogout($user->fresh());

        $this->assertNotNull($user->fresh()->last_logout_at);
        $this->assertSame('logout', $user->fresh()->activities()->orderByDesc('id')->first()->event);
    }
}
