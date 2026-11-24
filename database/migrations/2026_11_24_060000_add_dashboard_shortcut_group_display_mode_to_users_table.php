<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'dashboard_shortcut_group_display_mode')) {
                $table->enum('dashboard_shortcut_group_display_mode', ['list', 'nav-pills'])
                    ->default('list')
                    ->after('dashboard_shortcuts');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dashboard_shortcut_group_display_mode')) {
                $table->dropColumn('dashboard_shortcut_group_display_mode');
            }
        });
    }
};
