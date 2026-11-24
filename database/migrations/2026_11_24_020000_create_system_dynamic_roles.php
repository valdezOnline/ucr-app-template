<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates protected dynamic roles that mirror the legacy system flags
 * (is_admin, is_manager, is_supervisor) and backfills user_roles from them.
 *
 * The legacy columns are intentionally kept so both systems agree during the
 * transition; they will be removed in a later migration.
 */
return new class extends Migration {
    /**
     * Legacy flag => protected dynamic role definition.
     */
    private const SYSTEM_ROLES = [
        'is_admin' => [
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Full system access (migrated from the legacy admin flag)',
            'color' => '#dc3545',
        ],
        'is_manager' => [
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Management privileges (migrated from the legacy manager flag)',
            'color' => '#0d6efd',
        ],
        'is_supervisor' => [
            'name' => 'supervisor',
            'display_name' => 'Supervisor',
            'description' => 'Supervisory access (migrated from the legacy supervisor flag)',
            'color' => '#198754',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_active');
        });

        $now = now();

        foreach (self::SYSTEM_ROLES as $flag => $definition) {
            $roleId = DB::table('roles')->where('name', $definition['name'])->value('id');

            if ($roleId) {
                DB::table('roles')->where('id', $roleId)->update([
                    'is_system' => true,
                    'updated_at' => $now,
                ]);
            } else {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => $definition['name'],
                    'display_name' => $definition['display_name'],
                    'description' => $definition['description'],
                    'color' => $definition['color'],
                    'is_active' => true,
                    'is_system' => true,
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
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
     */
    public function down(): void
    {
        $names = array_column(self::SYSTEM_ROLES, 'name');

        // user_roles rows are removed by the foreign key cascade.
        DB::table('roles')->whereIn('name', $names)->where('is_system', true)->delete();

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
