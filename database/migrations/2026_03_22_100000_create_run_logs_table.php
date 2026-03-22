<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('run_logs', function (Blueprint $table) {
            $table->id();

            // Nullable FKs — a log belongs to a run OR a provisioned resource
            $table->foreignId('terraform_run_id')
                ->nullable()
                ->constrained('terraform_runs')
                ->cascadeOnDelete();

            $table->foreignId('provisioned_resource_id')
                ->nullable()
                ->constrained('provisioned_resources')
                ->cascadeOnDelete();

            // Denormalized FK for efficient per-environment queries
            $table->foreignId('environment_id')
                ->nullable()
                ->constrained('environments')
                ->cascadeOnDelete();

            // terraform | resource
            $table->string('source', 20)->default('terraform');

            // DEBUG | INFO | WARN | ERROR
            $table->string('level', 10)->default('INFO');

            $table->text('message');

            // Append-only — no updated_at
            $table->timestamp('created_at')->useCurrent();

            // ── Indexes ──────────────────────────────────────────────────────
            $table->index('terraform_run_id');
            $table->index(['terraform_run_id', 'id']); // incremental polling by run
            $table->index('provisioned_resource_id');
            $table->index(['provisioned_resource_id', 'id']); // incremental polling by resource
            $table->index('environment_id');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('run_logs');
    }
};
