<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-runs the legacy flag => system role backfill for users created after
 * 2026_11_24_020000 ran. Idempotent: existing user_roles rows are left alone.
 */
return new class extends Migration {
    /**
     * Legacy flag => system role name.
     */
    private const FLAG_TO_ROLE = [
        'is_admin' => 'admin',
        'is_manager' => 'manager',
        'is_supervisor' => 'supervisor',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        foreach (self::FLAG_TO_ROLE as $flag => $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');

            if (!$roleId) {
                continue;
            }

            $userIds = DB::table('users')->where($flag, true)->pluck('id');

            foreach ($userIds as $userId) {
                DB::table('user_roles')->insertOrIgnore([
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'assigned_by' => null,
                    'assigned_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Backfilled assignments are indistinguishable from manual ones, so
     * nothing is removed here. The previous migration's down() handles roles.
     */
    public function down(): void
    {
        //
    }
};
