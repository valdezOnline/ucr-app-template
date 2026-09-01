<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            // Make sure all required HR fields exist and are properly configured
            if (!Schema::hasColumn('users', 'net_id')) {
                $table->string('net_id')->unique()->after('id');
            }

            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->after('net_id');
            }

            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->after('first_name');
            }

            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('last_name');
            }

            if (!Schema::hasColumn('users', 'title')) {
                $table->string('title')->nullable()->after('department');
            }

            if (!Schema::hasColumn('users', 'supervisor_net_id')) {
                $table->string('supervisor_net_id')->nullable()->after('title');
            }

            if (!Schema::hasColumn('users', 'supervisor_name')) {
                $table->string('supervisor_name')->nullable()->after('supervisor_net_id');
            }

            // Ensure role columns exist
            if (!Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('supervisor_name');
            }

            if (!Schema::hasColumn('users', 'is_manager')) {
                $table->boolean('is_manager')->default(false)->after('is_admin');
            }

            if (!Schema::hasColumn('users', 'is_supervisor')) {
                $table->boolean('is_supervisor')->default(false)->after('is_manager');
            }

            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_supervisor');
            }
        });

        // Update existing users to have proper name field based on first_name and last_name
        if (Schema::hasColumn('users', 'name') && Schema::hasColumn('users', 'first_name') && Schema::hasColumn('users', 'last_name')) {
            DB::statement("UPDATE users SET name = CONCAT(first_name, ' ', last_name) WHERE name IS NULL OR name = ''");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We're not dropping columns in down() to prevent data loss
        // If you need to revert, create a separate migration
    }
};
