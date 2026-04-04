<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ansible_task_runs', function (Blueprint $table) {
            $table->id();

            // Exactly one of these is set
            $table->foreignId('ansible_run_id')
                ->nullable()
                ->constrained('ansible_runs')
                ->cascadeOnDelete();

            $table->foreignId('runbook_run_id')
                ->nullable()
                ->constrained('runbook_runs')
                ->cascadeOnDelete();

            // Nullable — task template may have been deleted
            $table->foreignId('playbook_task_id')
                ->nullable()
                ->constrained('ansible_playbook_tasks')
                ->nullOnDelete();

            // Snapshot values at execution time
            $table->string('task_name', 500);
            $table->string('module', 100)->nullable();
            $table->unsignedInteger('position')->nullable();

            // PENDING | RUNNING | OK | FAILED | SKIPPED | UNREACHABLE
            $table->string('status')->default('PENDING');
            $table->text('output')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('ansible_run_id');
            $table->index('runbook_run_id');
            $table->index('playbook_task_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansible_task_runs');
    }
};
