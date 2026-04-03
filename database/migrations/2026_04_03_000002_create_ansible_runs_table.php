<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ansible_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_id')
                ->constrained('deployments')
                ->cascadeOnDelete();

            // QUEUED | INITIALIZING | RUNNING | COMPLETED | FAILED
            $table->string('status')->default('QUEUED');

            // configure | teardown
            $table->string('action')->default('configure');

            $table->string('provider_type')->nullable();
            $table->string('workspace_path')->nullable();

            // Snapshot dos extra_vars injetados no processo (auditoria).
            $table->json('extra_vars_json')->nullable();

            // Snapshot do inventory.ini gerado (auditoria).
            $table->longText('inventory_ini')->nullable();

            // Conteúdo do ansible_outputs.json lido após a execução do playbook.
            $table->json('output_json')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('deployment_id');
            $table->index('status');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansible_runs');
    }
};
