<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->foreignId('environment_template_version_id')
                ->nullable()
                ->after('project_id')
                ->constrained('environment_template_versions')
                ->nullOnDelete();

            // Stores the filled-in values from the template fields (environment_configuration)
            $table->json('configuration_json')->nullable()->after('environment_template_version_id');

            // Additional environment context fields
            $table->text('description')->nullable()->after('name');
            $table->string('execution_mode', 20)->default('manual')->after('status');

            $table->index('environment_template_version_id');
        });
    }

    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->dropForeign(['environment_template_version_id']);
            $table->dropIndex(['environment_template_version_id']);
            $table->dropColumn([
                'environment_template_version_id',
                'configuration_json',
                'description',
                'execution_mode',
            ]);
        });
    }
};
