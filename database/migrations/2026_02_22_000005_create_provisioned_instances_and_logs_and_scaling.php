<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provisioned Resource taxonomy.
 *
 * provisioned_resource_kinds  — high-level categories
 *   e.g. compute | storage | serverless | database | network | container
 *
 * provisioned_resource_types  — specific cloud-provider implementations
 *   e.g. aws_ec2 (compute/AWS) | gcp_compute_engine (compute/GCP)
 *        aws_lambda (serverless/AWS) | aws_s3 (storage/AWS)
 *        aws_rds (database/AWS) | gcp_cloud_sql (database/GCP)
 */
return new class extends Migration {
    public function up(): void
    {
        // ── 1. Kinds ─────────────────────────────────────────────────────────
        Schema::create('provisioned_resource_kinds', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();   // compute | storage | serverless …
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── 2. Types ──────────────────────────────────────────────────────────
        Schema::create('provisioned_resource_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provisioned_resource_kind_id')
                ->constrained('provisioned_resource_kinds')
                ->cascadeOnDelete();

            // null == cloud-agnostic / not tied to a specific provider
            $table->foreignId('provider_id')
                ->nullable()
                ->constrained('providers')
                ->nullOnDelete();

            // unique slug, e.g. "aws_ec2", "gcp_compute_engine", "aws_lambda"
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // Terraform resource type identifier, e.g. "aws_instance"
            $table->string('provider_resource_identifier')->nullable();

            // JSON Schema describing the expected attributes for this type
            $table->json('attributes_schema_json')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('provisioned_resource_kind_id');
            $table->index('provider_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provisioned_resource_types');
        Schema::dropIfExists('provisioned_resource_kinds');
    }
};
