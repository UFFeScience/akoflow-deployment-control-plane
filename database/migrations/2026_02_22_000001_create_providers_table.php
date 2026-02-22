<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('status')->default('ACTIVE');
            $table->string('health_status')->default('HEALTHY');
            $table->text('health_message')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('health_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
