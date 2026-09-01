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
        // Update existing applications to have url_type = 'external' where they have a URL
        // and url_type is currently 'external' (the default)
        DB::table('applications')
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->where('url_type', 'external')
            ->update([
                    'url_type' => 'external'
                ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this data migration
        // The url_type field will be dropped by the structure migration if rolled back
    }
};
