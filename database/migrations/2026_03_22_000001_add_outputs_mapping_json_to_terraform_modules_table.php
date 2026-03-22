<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('environment_template_terraform_modules', function (Blueprint $table) {
            // Declares which Terraform outputs map to ProvisionedResource fields.
            // Consumed by CreateProvisionedResourcesService after a successful apply.
            $table->json('outputs_mapping_json')->nullable()->after('tfvars_mapping_json');
        });
    }

    public function down(): void
    {
        Schema::table('environment_template_terraform_modules', function (Blueprint $table) {
            $table->dropColumn('outputs_mapping_json');
        });
    }
};
