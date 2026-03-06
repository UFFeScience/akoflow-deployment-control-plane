<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('experiment_template_terraform_modules', function (Blueprint $table) {
            // Lista de nomes de variáveis de ambiente que o container Terraform precisa.
            // O serviço lê cada chave do ambiente da aplicação e as escreve no .env
            // do workspace em tempo de execução do job.
            //
            // Exemplo:
            //   ["AWS_ACCESS_KEY_ID", "AWS_SECRET_ACCESS_KEY", "AWS_SESSION_TOKEN"]
            //   ["GOOGLE_CREDENTIALS", "GOOGLE_PROJECT"]
            //
            // Com isso, nenhum código específico de provider precisa existir no
            // TerraformWorkspaceService — o template declara o que precisa.
            $table->json('credential_env_keys')->nullable()->after('tfvars_mapping_json');
        });
    }

    public function down(): void
    {
        Schema::table('experiment_template_terraform_modules', function (Blueprint $table) {
            $table->dropColumn('credential_env_keys');
        });
    }
};
