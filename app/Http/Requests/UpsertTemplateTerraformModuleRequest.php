<?php

namespace App\Http\Requests;

use App\Models\ExperimentTemplateTerraformModule;
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
                Rule::in(ExperimentTemplateTerraformModule::BUILT_IN_SLUGS),
            ],

            // ── HCL customizado (todos opcionais; se presente, sobrepõe slug) ─
            'main_tf'      => 'nullable|string',
            'variables_tf' => 'nullable|string',
            'outputs_tf'   => 'nullable|string',

            // ── Mapeamento campo → variável Terraform ─────────────────────────
            'tfvars_mapping_json'                              => 'nullable|array',
            'tfvars_mapping_json.experiment_configuration'     => 'nullable|array',
            'tfvars_mapping_json.instance_configurations'      => 'nullable|array',

            // Lista de nomes de env vars que o container Terraform precisa ter.
            'credential_env_keys'   => 'nullable|array',
            'credential_env_keys.*' => 'string',
        ];
    }

    public function messages(): array
    {
        return [
            'module_slug.in' => 'O slug deve ser um módulo built-in válido: ' .
                implode(', ', ExperimentTemplateTerraformModule::BUILT_IN_SLUGS) . '.',
        ];
    }
}
