<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_credential_health_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_credential_id')
                ->constrained('provider_credentials')
                ->cascadeOnDelete();
            $table->string('health_status');
            $table->text('health_message')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index('provider_credential_id');
            $table->index('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_credential_health_logs');
    }
};
