<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('experiments', function (Blueprint $table) {
            $table->foreignId('experiment_template_version_id')
                ->nullable()
                ->after('project_id')
                ->constrained('experiment_template_versions')
                ->nullOnDelete();

            // Stores the filled-in values from the template fields (experiment_configuration + instance_configurations)
            $table->json('configuration_json')->nullable()->after('experiment_template_version_id');

            // Additional experiment context fields
            $table->text('description')->nullable()->after('name');
            $table->string('execution_mode', 20)->default('manual')->after('status');

            $table->index('experiment_template_version_id');
        });
    }

    public function down(): void
    {
        Schema::table('experiments', function (Blueprint $table) {
            $table->dropForeign(['experiment_template_version_id']);
            $table->dropIndex(['experiment_template_version_id']);
            $table->dropColumn([
                'experiment_template_version_id',
                'configuration_json',
                'description',
                'execution_mode',
            ]);
        });
    }
};
