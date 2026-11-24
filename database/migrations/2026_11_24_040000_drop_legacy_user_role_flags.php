<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the legacy is_admin, is_manager, and is_supervisor columns from users.
 * Role membership is now stored only in user_roles via the system roles
 * (admin, manager, supervisor).
 */
return new class extends Migration {
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array_keys(self::FLAG_TO_ROLE));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (array_keys(self::FLAG_TO_ROLE) as $flag) {
                $table->boolean($flag)->default(false);
            }
        });

        // Restore flags from role membership.
        foreach (self::FLAG_TO_ROLE as $flag => $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');

            if (!$roleId) {
                continue;
            }

            $userIds = DB::table('user_roles')->where('role_id', $roleId)->pluck('user_id');

            DB::table('users')->whereIn('id', $userIds)->update([$flag => true]);
        }
    }
};
