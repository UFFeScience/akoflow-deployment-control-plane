<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('run_logs', function (Blueprint $table) {
            $table->foreignId('ansible_activity_run_id')
                ->nullable()
                ->after('runbook_run_id')
                ->constrained('ansible_activity_runs')
                ->cascadeOnDelete();

            $table->index('ansible_activity_run_id');
        });
    }

    public function down(): void
    {
        Schema::table('run_logs', function (Blueprint $table) {
            $table->dropForeign(['ansible_activity_run_id']);
            $table->dropIndex(['ansible_activity_run_id']);
            $table->dropColumn('ansible_activity_run_id');
        });
    }
};
