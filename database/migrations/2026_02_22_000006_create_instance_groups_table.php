<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instance_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained('clusters')->cascadeOnDelete();
            $table->foreignId('instance_type_id')->constrained('instance_types')->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index('cluster_id');
            $table->index('instance_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instance_groups');
    }
};
