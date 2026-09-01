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
            $table->boolean('supports_api_auth')->default(false)->after('is_active');
            $table->string('api_key', 64)->nullable()->unique()->after('supports_api_auth');
            $table->string('api_secret', 255)->nullable()->after('api_key');
            $table->json('allowed_domains')->nullable()->after('api_secret');
            $table->timestamp('api_key_expires_at')->nullable()->after('allowed_domains');
            $table->boolean('api_active')->default(true)->after('api_key_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'supports_api_auth',
                'api_key',
                'api_secret',
                'allowed_domains',
                'api_key_expires_at',
                'api_active'
            ]);
        });
    }
};
