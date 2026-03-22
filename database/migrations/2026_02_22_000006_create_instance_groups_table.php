<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provisioned resources and their logs.
 *
 * provisioned_resources — records one cloud resource that was created by a
 *   terraform apply for a given Deployment. Created AFTER the apply succeeds,
 *   by parsing output_json. Never pre-created.
 *
 * resource_logs — append-only log stream per resource.
 */
return new class extends Migration {
    public function up(): void
    {
        // ── 1. Provisioned resources ─────────────────────────────────────────
        Schema::create('provisioned_resources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deployment_id')
                ->constrained('deployments')
                ->cascadeOnDelete();

            $table->foreignId('provisioned_resource_type_id')
                ->constrained('provisioned_resource_types')
                ->restrictOnDelete();

            // Opaque ID returned by the cloud provider (e.g. "i-0abc123")
            $table->string('provider_resource_id')->nullable();

            $table->string('name')->nullable();

            // PENDING | CREATING | RUNNING | STOPPING | STOPPED | ERROR | DESTROYED
            $table->string('status')->default('PENDING');
            $table->string('health_status')->nullable();
            $table->timestamp('last_health_check_at')->nullable();

            $table->string('public_ip', 45)->nullable();
            $table->string('private_ip', 45)->nullable();

            // Arbitrary key/value bag for type-specific runtime data
            $table->json('metadata_json')->nullable();

            $table->timestamps();

            $table->index('deployment_id');
            $table->index('provisioned_resource_type_id');
            $table->index('status');
            $table->index('health_status');
        });

        // ── 2. Resource logs ─────────────────────────────────────────────────
        Schema::create('resource_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provisioned_resource_id')
                ->constrained('provisioned_resources')
                ->cascadeOnDelete();

            // DEBUG | INFO | WARN | ERROR
            $table->string('level')->default('INFO');
            $table->text('message');

            // Append-only: only created_at, no updated_at
            $table->timestamp('created_at')->useCurrent();

            $table->index('provisioned_resource_id');
            $table->index('level');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_logs');
        Schema::dropIfExists('provisioned_resources');
    }
};
