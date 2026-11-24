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
            if (!Schema::hasColumn('users', 'dashboard_group_display_mode')) {
                $table->enum('dashboard_group_display_mode', ['tabs', 'accordion'])
                    ->default('accordion')
                    ->after('dashboard_groups');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dashboard_group_display_mode')) {
                $table->dropColumn('dashboard_group_display_mode');
            }
        });
    }
};
