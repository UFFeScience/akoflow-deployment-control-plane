<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('environment_template_ansible_playbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_configuration_id')
                ->constrained('environment_template_provider_configurations')
                ->cascadeOnDelete();
            $table->string('playbook_slug')->nullable();
            $table->longText('playbook_yaml')->nullable();
            $table->text('inventory_template')->nullable();
            $table->json('vars_mapping_json')->nullable();
            $table->json('outputs_mapping_json')->nullable();
            $table->json('credential_env_keys')->nullable();
            $table->json('roles_json')->nullable();
            $table->timestamps();
            $table->unique('provider_configuration_id', 'ansible_playbooks_config_unique');
            $table->index('playbook_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_template_ansible_playbooks');
    }
};
