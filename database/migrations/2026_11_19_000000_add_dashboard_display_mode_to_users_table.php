<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'dashboard_display_mode')) {
                $table->enum('dashboard_display_mode', ['normal', 'small', 'text'])->default('normal')->after('dashboard_shortcuts');
            }

            if (!Schema::hasColumn('users', 'dashboard_groups')) {
                $table->json('dashboard_groups')->nullable()->after('dashboard_display_mode');
            }

            if (!Schema::hasColumn('users', 'dashboard_application_groups')) {
                $table->json('dashboard_application_groups')->nullable()->after('dashboard_groups');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dashboard_application_groups')) {
                $table->dropColumn('dashboard_application_groups');
            }

            if (Schema::hasColumn('users', 'dashboard_groups')) {
                $table->dropColumn('dashboard_groups');
            }

            if (Schema::hasColumn('users', 'dashboard_display_mode')) {
                $table->dropColumn('dashboard_display_mode');
            }
        });
    }
};
