<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ansible_playbooks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_configuration_id')
                ->constrained('environment_template_provider_configurations')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // after_provision | when_ready | manual | before_teardown
            $table->string('trigger')->default('after_provision');

            $table->string('playbook_slug')->nullable();
            $table->longText('playbook_yaml')->nullable();
            $table->text('inventory_template')->nullable();

            $table->json('vars_mapping_json')->nullable();
            $table->json('outputs_mapping_json')->nullable();
            $table->json('credential_env_keys')->nullable();
            $table->json('roles_json')->nullable();

            // Ordering within the same trigger group
            $table->unsignedInteger('position')->default(0);
            $table->boolean('enabled')->default(true);

            $table->timestamps();

            $table->index('provider_configuration_id');
            $table->index(['provider_configuration_id', 'trigger']);
            $table->index('playbook_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansible_playbooks');
    }
};
