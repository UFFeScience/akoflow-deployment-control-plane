<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ansible_playbook_tasks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ansible_playbook_id');

            $table->unsignedInteger('position')->default(0);
            $table->string('name', 500);
            $table->string('module', 100)->nullable();   // apt, systemd, copy, command...
            $table->json('module_args_json')->nullable(); // structured module arguments
            $table->string('when_condition', 500)->nullable();
            $table->boolean('become')->default(false);
            $table->json('tags_json')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('ansible_playbook_id');
            $table->index(['ansible_playbook_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansible_playbook_tasks');
    }
};
