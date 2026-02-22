<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('experiment_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('runtime_type');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('owner_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->timestamps();

            $table->index('runtime_type');
            $table->index('owner_organization_id');
        });

        Schema::create('experiment_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('experiment_templates')->cascadeOnDelete();
            $table->string('version');
            $table->json('definition_json');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('template_id');
            $table->index('version');
            $table->unique(['template_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_template_versions');
        Schema::dropIfExists('experiment_templates');
    }
};
