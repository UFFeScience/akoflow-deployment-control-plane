<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ansible_activity_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deployment_id')
                ->constrained('deployments')
                ->cascadeOnDelete();

            // Nullable: preserves history when the activity template is deleted
            $table->foreignId('playbook_id')
                ->nullable()
                ->constrained('ansible_playbooks')
                ->nullOnDelete();

            // Snapshot of the activity name at execution time
            $table->string('playbook_name');

            // Which trigger fired this run
            $table->string('trigger');

            // QUEUED | INITIALIZING | RUNNING | COMPLETED | FAILED
            $table->string('status')->default('QUEUED');

            $table->string('provider_type')->nullable();
            $table->string('triggered_by')->nullable();   // user_id or 'system'
            $table->string('workspace_path')->nullable();
            $table->json('extra_vars_json')->nullable();
            $table->longText('inventory_ini')->nullable();
            $table->json('output_json')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('deployment_id');
            $table->index('playbook_id');
            $table->index('status');
            $table->index('trigger');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansible_activity_runs');
    }
};
