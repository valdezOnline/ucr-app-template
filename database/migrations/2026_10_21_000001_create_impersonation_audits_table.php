<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('impersonation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impersonator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('impersonated_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('auth_type', 32);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('start_ip', 45)->nullable();
            $table->string('end_ip', 45)->nullable();
            $table->text('start_user_agent')->nullable();
            $table->text('end_user_agent')->nullable();
            $table->string('stop_reason', 64)->nullable();
            $table->timestamps();

            $table->index(['impersonator_user_id', 'started_at']);
            $table->index(['impersonated_user_id', 'started_at']);
            $table->index(['ended_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impersonation_audits');
    }
};
