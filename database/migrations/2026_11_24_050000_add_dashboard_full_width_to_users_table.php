<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'dashboard_full_width')) {
                $table->boolean('dashboard_full_width')
                    ->default(false)
                    ->after('dashboard_group_display_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dashboard_full_width')) {
                $table->dropColumn('dashboard_full_width');
            }
        });
    }
};
