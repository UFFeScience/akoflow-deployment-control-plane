<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environment_template_ansible_playbooks', function (Blueprint $table) {
            $table->string('phase')->default('provision')->after('provider_configuration_id');
        });

        // Migrate existing rows to phase = 'provision'
        \DB::table('environment_template_ansible_playbooks')->update(['phase' => 'provision']);

        Schema::table('environment_template_ansible_playbooks', function (Blueprint $table) {
            $table->dropUnique('ansible_playbooks_config_unique');
            $table->unique(['provider_configuration_id', 'phase'], 'ansible_playbooks_config_phase_unique');
        });
    }

    public function down(): void
    {
        Schema::table('environment_template_ansible_playbooks', function (Blueprint $table) {
            $table->dropUnique('ansible_playbooks_config_phase_unique');
            $table->dropColumn('phase');
            $table->unique('provider_configuration_id', 'ansible_playbooks_config_unique');
        });
    }
};
