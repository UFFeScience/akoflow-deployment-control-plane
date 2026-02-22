<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiment_instance_configs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('experiment_instance_id')
                ->constrained('experiment_instances')
                ->cascadeOnDelete();

            $table->string('instance_type', 100);
            // t3.large, n1-standard-4, hpc.medium etc

            $table->unsignedInteger('quantity')->default(1);

            $table->unsignedInteger('cpu')->nullable();
            $table->unsignedInteger('memory_gb')->nullable();
            $table->unsignedInteger('gpu')->nullable();

            $table->timestamps();

            $table->index('experiment_instance_id');
            $table->index('instance_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_instance_configs');
    }
};
