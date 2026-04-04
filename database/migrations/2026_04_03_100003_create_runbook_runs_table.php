<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('runbook_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_id')
                ->constrained('deployments')
                ->cascadeOnDelete();

            // Nullable so history is preserved even if the runbook template is deleted
            $table->foreignId('runbook_id')
                ->nullable()
                ->constrained('environment_template_runbooks')
                ->nullOnDelete();

            // Snapshot of the runbook name at execution time
            $table->string('runbook_name');

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
            $table->index('runbook_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runbook_runs');
    }
};
