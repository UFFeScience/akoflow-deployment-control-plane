<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('environment_template_provider_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_version_id')
                ->constrained('environment_template_versions')
                ->cascadeOnDelete();
            $table->string('name');
            $table->json('applies_to_providers');
            $table->timestamps();
            $table->index('template_version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_template_provider_configurations');
    }
};
