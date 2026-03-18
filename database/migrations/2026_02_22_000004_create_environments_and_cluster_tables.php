<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cluster_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_version_id')->constrained('environment_template_versions')->cascadeOnDelete();
            $table->json('custom_parameters_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('template_version_id');
        });

        Schema::create('clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_id')->constrained('environments')->cascadeOnDelete();
            $table->foreignId('cluster_template_id')->constrained('cluster_templates')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->string('region')->nullable();
            $table->string('environment_type');
            $table->string('name');
            $table->string('status')->default('PROVISIONING');
            $table->timestamps();

            $table->index('environment_id');
            $table->index('provider_id');
            $table->index('cluster_template_id');
            $table->index('status');
            $table->index('environment_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clusters');
        Schema::dropIfExists('cluster_templates');
    }
};
