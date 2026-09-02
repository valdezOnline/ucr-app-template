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
        Schema::table('applications', function (Blueprint $table) {
            $table->boolean('external_auth_enabled')
                ->default(false)
                ->after('api_active');
            $table->string('external_auth_callback_url', 2048)
                ->nullable()
                ->after('external_auth_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'external_auth_enabled',
                'external_auth_callback_url',
            ]);
        });
    }
};
