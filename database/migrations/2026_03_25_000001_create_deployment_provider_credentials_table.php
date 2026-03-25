<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * deployment_provider_credentials — pivot table that allows a single deployment
 * to reference multiple provider + credential pairs (e.g. one for AWS and one
 * for GCP in the same multi-cloud deployment).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployment_provider_credentials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deployment_id')
                ->constrained('deployments')
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->constrained('providers')
                ->cascadeOnDelete();

            $table->foreignId('provider_credential_id')
                ->nullable()
                ->constrained('provider_credentials')
                ->nullOnDelete();

            // Denormalized slug of the provider for quick lookups.
            // Populated from providers.slug at insert time.
            $table->string('provider_slug')->nullable();

            $table->timestamps();

            $table->index('deployment_id');
            $table->unique(['deployment_id', 'provider_id'], 'uq_deployment_provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_provider_credentials');
    }
};
