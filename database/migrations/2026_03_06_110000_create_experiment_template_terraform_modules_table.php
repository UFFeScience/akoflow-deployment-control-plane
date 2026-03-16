<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('experiment_template_terraform_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_version_id')
                ->constrained('experiment_template_versions')
                ->cascadeOnDelete();

            // Tipo de provider – obrigatório, identifica o cloud alvo do módulo.
            $table->string('provider_type'); // aws | gcp | azure | custom

            // Referência a módulo built-in da plataforma (ex.: 'aws_nvflare', 'gcp_gke').
            $table->string('module_slug')->nullable();

            // Conteúdo HCL customizado – sobrepõe o módulo built-in quando presente.
            $table->longText('main_tf')->nullable();
            $table->longText('variables_tf')->nullable();
            $table->longText('outputs_tf')->nullable();

            // Mapeamento configuration_json → variáveis Terraform.
            $table->json('tfvars_mapping_json')->nullable();

            // Lista de variáveis de ambiente que o container Terraform precisa
            // (ex.: ["AWS_ACCESS_KEY_ID", "AWS_SECRET_ACCESS_KEY"]).
            $table->json('credential_env_keys')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            // Um módulo por version+provider
            $table->unique(['template_version_id', 'provider_type'], 'terraform_modules_version_provider_unique');
            $table->index('module_slug');
            $table->index('provider_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_template_terraform_modules');
    }
};
