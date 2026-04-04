<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('run_logs', function (Blueprint $table) {
            $table->foreignId('runbook_run_id')
                ->nullable()
                ->after('ansible_run_id')
                ->constrained('runbook_runs')
                ->cascadeOnDelete();

            $table->index('runbook_run_id');
        });
    }

    public function down(): void
    {
        Schema::table('run_logs', function (Blueprint $table) {
            $table->dropForeign(['runbook_run_id']);
            $table->dropIndex(['runbook_run_id']);
            $table->dropColumn('runbook_run_id');
        });
    }
};
