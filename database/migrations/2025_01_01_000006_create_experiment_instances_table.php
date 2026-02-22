<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiment_instances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('experiment_id')
                ->constrained('experiments')
                ->cascadeOnDelete();

            $table->string('provider', 50);
            // aws, gcp, azure, hpc, local

            $table->string('region', 100)->nullable();

            $table->string('status', 50)->default('pending');
            // pending, provisioning, running, failed, terminated

            $table->timestamps();

            $table->index('experiment_id');
            $table->index('provider');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_instances');
    }
};
