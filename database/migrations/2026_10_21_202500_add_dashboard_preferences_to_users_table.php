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
            if (!Schema::hasColumn('users', 'dashboard_application_order')) {
                $table->json('dashboard_application_order')->nullable()->after('is_active');
            }

            if (!Schema::hasColumn('users', 'dashboard_shortcuts')) {
                $table->json('dashboard_shortcuts')->nullable()->after('dashboard_application_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dashboard_shortcuts')) {
                $table->dropColumn('dashboard_shortcuts');
            }

            if (Schema::hasColumn('users', 'dashboard_application_order')) {
                $table->dropColumn('dashboard_application_order');
            }
        });
    }
};
