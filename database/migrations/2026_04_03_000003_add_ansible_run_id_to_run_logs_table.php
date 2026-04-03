<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('run_logs', function (Blueprint $table) {
            // FK nullable — um log pertence a um TerraformRun OU a um AnsibleRun
            $table->foreignId('ansible_run_id')
                ->nullable()
                ->after('terraform_run_id')
                ->constrained('ansible_runs')
                ->cascadeOnDelete();

            $table->index('ansible_run_id');
            $table->index(['ansible_run_id', 'id']); // incremental polling por run
        });
    }

    public function down(): void
    {
        Schema::table('run_logs', function (Blueprint $table) {
            $table->dropForeign(['ansible_run_id']);
            $table->dropIndex(['ansible_run_id', 'id']);
            $table->dropColumn('ansible_run_id');
        });
    }
};
