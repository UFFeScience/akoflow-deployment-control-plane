<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('environment_template_runbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_configuration_id')
                ->constrained('environment_template_provider_configurations')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Generated from tasks OR written manually (same as ansible_playbook.playbook_yaml)
            $table->longText('playbook_yaml')->nullable();

            $table->json('vars_mapping_json')->nullable();
            $table->json('credential_env_keys')->nullable();
            $table->json('roles_json')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('provider_configuration_id');
        });

        // Add FK from ansible_playbook_tasks to runbooks (table now exists)
        Schema::table('ansible_playbook_tasks', function (Blueprint $table) {
            $table->foreign('runbook_id')
                ->references('id')
                ->on('environment_template_runbooks')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ansible_playbook_tasks', function (Blueprint $table) {
            $table->dropForeign(['runbook_id']);
        });
        Schema::dropIfExists('environment_template_runbooks');
    }
};
