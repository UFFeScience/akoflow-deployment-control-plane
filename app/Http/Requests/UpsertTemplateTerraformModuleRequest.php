<?php

namespace App\Http\Requests;

use App\Models\EnvironmentTemplateTerraformModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertTemplateTerraformModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ── Módulo built-in ───────────────────────────────────────────────
            'module_slug'  => [
                'nullable',
                'string',
                Rule::in(EnvironmentTemplateTerraformModule::BUILT_IN_SLUGS),
            ],

            // ── HCL customizado (todos opcionais; se presente, sobrepõe slug) ─
            'main_tf'      => 'nullable|string',
            'variables_tf' => 'nullable|string',
            'outputs_tf'   => 'nullable|string',

            // ── Mapeamento campo → variável Terraform ─────────────────────────
            'tfvars_mapping_json'                              => 'nullable|array',
            'tfvars_mapping_json.environment_configuration'     => 'nullable|array',

            // ── Mapeamento de outputs Terraform → recurso provisionado ────────
            'outputs_mapping_json'                                     => 'nullable|array',
            'outputs_mapping_json.resources'                           => 'nullable|array',
            'outputs_mapping_json.resources.*.name'                    => 'nullable|string',
            'outputs_mapping_json.resources.*.terraform_type'          => 'nullable|string',
            'outputs_mapping_json.resources.*.outputs'                 => 'nullable|array',
            'outputs_mapping_json.resources.*.outputs.provider_resource_id' => 'nullable|string',
            'outputs_mapping_json.resources.*.outputs.public_ip'       => 'nullable|string',
            'outputs_mapping_json.resources.*.outputs.private_ip'      => 'nullable|string',
            'outputs_mapping_json.resources.*.outputs.iframe_url'      => 'nullable|string',
            'outputs_mapping_json.resources.*.outputs.metadata'        => 'nullable|array',

            // Lista de nomes de env vars que o container Terraform precisa ter.
            'credential_env_keys'   => 'nullable|array',
            'credential_env_keys.*' => 'string',
        ];
    }

    public function messages(): array
    {
        return [
            'module_slug.in' => 'O slug deve ser um módulo built-in válido: ' .
                implode(', ', EnvironmentTemplateTerraformModule::BUILT_IN_SLUGS) . '.',
        ];
    }
}
