<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes provisioned_resource_type_id nullable on provisioned_resources.
 *
 * The type lookup is best-effort (matched by Terraform resource type string).
 * When no matching ProvisionedResourceType row exists the record should still
 * be created so the provisioned resource is tracked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provisioned_resources', function (Blueprint $table) {
            // Drop the existing NOT NULL foreign key constraint first
            $table->dropForeign(['provisioned_resource_type_id']);

            // Re-add as nullable with the same foreign key
            $table->foreignId('provisioned_resource_type_id')
                ->nullable()
                ->change();

            $table->foreign('provisioned_resource_type_id')
                ->references('id')
                ->on('provisioned_resource_types')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('provisioned_resources', function (Blueprint $table) {
            $table->dropForeign(['provisioned_resource_type_id']);

            $table->foreignId('provisioned_resource_type_id')
                ->nullable(false)
                ->change();

            $table->foreign('provisioned_resource_type_id')
                ->references('id')
                ->on('provisioned_resource_types')
                ->restrictOnDelete();
        });
    }
};
