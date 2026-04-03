<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('environment_template_terraform_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_configuration_id')
                ->constrained('environment_template_provider_configurations')
                ->cascadeOnDelete();
            $table->string('module_slug')->nullable();
            $table->longText('main_tf')->nullable();
            $table->longText('variables_tf')->nullable();
            $table->longText('outputs_tf')->nullable();
            $table->json('tfvars_mapping_json')->nullable();
            $table->json('outputs_mapping_json')->nullable();
            $table->json('credential_env_keys')->nullable();
            $table->timestamps();
            $table->unique('provider_configuration_id', 'terraform_modules_config_unique');
            $table->index('module_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_template_terraform_modules');
    }
};
