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
                ->unique()
                ->constrained('experiment_template_versions')
                ->cascadeOnDelete();

            // Referência a módulo built-in da plataforma (ex.: 'aws_nvflare', 'gcp_gke').
            // Se preenchido, o workspace usa o módulo estático em infra/terraform/modules/{module_slug}.
            $table->string('module_slug')->nullable();

            // Tipo de provider derivado do módulo ou preenchido manualmente.
            // Usado para injetar credenciais corretas no .env do workspace.
            $table->string('provider_type')->nullable(); // aws | gcp | azure | custom

            // Conteúdo HCL customizado – sobrepõe o módulo built-in quando presente.
            $table->longText('main_tf')->nullable();
            $table->longText('variables_tf')->nullable();
            $table->longText('outputs_tf')->nullable();

            // Mapeamento configuration_json → variáveis Terraform.
            // Estrutura:
            // {
            //   "experiment_configuration": { "<campo_config>": "<tf_var_name>" },
            //   "instance_configurations": {
            //     "<instance_key>": { "<campo_config>": "<tf_var_name>" }
            //   }
            // }
            // Quando nulo, o sistema usa o mapeamento built-in baseado em provider_type.
            $table->json('tfvars_mapping_json')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index('module_slug');
            $table->index('provider_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiment_template_terraform_modules');
    }
};
