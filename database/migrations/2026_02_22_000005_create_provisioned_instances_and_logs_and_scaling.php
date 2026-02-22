<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provisioned_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained('clusters')->cascadeOnDelete();
            $table->foreignId('instance_type_id')->constrained('instance_types')->cascadeOnDelete();
            $table->string('provider_instance_id')->nullable();
            $table->string('role')->nullable();
            $table->string('status')->default('PROVISIONING');
            $table->string('health_status')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->string('public_ip')->nullable();
            $table->string('private_ip')->nullable();
            $table->timestamps();

            $table->index('cluster_id');
            $table->index('instance_type_id');
            $table->index('status');
            $table->index('health_status');
        });

        Schema::create('instance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provisioned_instance_id')->constrained('provisioned_instances')->cascadeOnDelete();
            $table->string('level');
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();

            $table->index('provisioned_instance_id');
            $table->index('level');
        });

        Schema::create('cluster_scaling_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained('clusters')->cascadeOnDelete();
            $table->string('action');
            $table->integer('old_value')->nullable();
            $table->integer('new_value')->nullable();
            $table->string('triggered_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('cluster_id');
            $table->index('action');
            $table->index('triggered_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_scaling_events');
        Schema::dropIfExists('instance_logs');
        Schema::dropIfExists('provisioned_instances');
    }
};
