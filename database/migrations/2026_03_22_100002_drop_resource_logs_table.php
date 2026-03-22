<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('resource_logs');
    }

    public function down(): void
    {
        Schema::create('resource_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provisioned_resource_id')
                ->constrained('provisioned_resources')
                ->cascadeOnDelete();
            $table->string('level', 10)->default('INFO');
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();

            $table->index('provisioned_resource_id');
            $table->index('level');
            $table->index('created_at');
        });
    }
};
