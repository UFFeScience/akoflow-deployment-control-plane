<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instance_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('name');
            $table->integer('vcpus')->nullable();
            $table->integer('memory_mb')->nullable();
            $table->integer('gpu_count')->nullable();
            $table->integer('storage_default_gb')->nullable();
            $table->string('network_bandwidth')->nullable();
            $table->string('region')->nullable();
            $table->string('status')->default('AVAILABLE');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('provider_id');
            $table->index('status');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instance_types');
    }
};
