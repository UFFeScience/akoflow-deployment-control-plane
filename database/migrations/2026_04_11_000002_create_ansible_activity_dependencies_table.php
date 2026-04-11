<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ansible_playbook_dependencies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('playbook_id')
                ->constrained('ansible_playbooks')
                ->cascadeOnDelete();

            $table->foreignId('depends_on_playbook_id')
                ->constrained('ansible_playbooks')
                ->cascadeOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['playbook_id', 'depends_on_playbook_id'], 'activity_deps_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ansible_playbook_dependencies');
    }
};
