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
            // Add URL type field (external, sub-application, folder)
            $table->enum('url_type', ['external', 'sub-application', 'folder'])
                ->default('external')
                ->after('url');

            // Add sub-path field for sub-applications and folders
            $table->string('sub_path', 500)
                ->nullable()
                ->after('url_type');

            // Add base domain field for sub-applications and folders
            $table->string('base_domain', 255)
                ->nullable()
                ->after('sub_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['url_type', 'sub_path', 'base_domain']);
        });
    }
};
