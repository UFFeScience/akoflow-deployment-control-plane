<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ansible_playbook_run_task_hosts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ansible_playbook_run_id')
                ->constrained('ansible_activity_runs')
                ->cascadeOnDelete();

            // Nullable to preserve history when task definition changes/deletes.
            $table->foreignId('ansible_playbook_task_id')
                ->nullable()
                ->constrained('ansible_playbook_tasks')
                ->nullOnDelete();

            $table->string('host', 255)->default('all');
            $table->string('task_name', 500);
            $table->string('module', 120)->nullable();
            $table->unsignedInteger('position')->nullable();

            // PENDING | RUNNING | OK | CHANGED | FAILED | SKIPPED | UNREACHABLE
            $table->string('status', 32)->default('PENDING');

            $table->longText('output')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['ansible_playbook_run_id', 'position'], 'aprth_run_position_idx');
            $table->index(['ansible_playbook_run_id', 'host'], 'aprth_run_host_idx');
            $table->index(['ansible_playbook_run_id', 'task_name'], 'aprth_run_task_idx');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansible_playbook_run_task_hosts');
    }
};
