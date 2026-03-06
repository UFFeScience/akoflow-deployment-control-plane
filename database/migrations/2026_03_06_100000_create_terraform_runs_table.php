<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('terraform_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
            $table->string('status')->default('QUEUED');
            // QUEUED | INITIALIZING | PLANNING | APPLYING | APPLIED | DESTROYING | DESTROYED | FAILED
            $table->string('provider_type')->nullable(); // aws | gcp
            $table->string('action')->default('apply');  // apply | destroy
            $table->string('workspace_path')->nullable();
            $table->json('tfvars_json')->nullable();
            $table->json('output_json')->nullable();
            $table->longText('logs')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('experiment_id');
            $table->index('status');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terraform_runs');
    }
};
