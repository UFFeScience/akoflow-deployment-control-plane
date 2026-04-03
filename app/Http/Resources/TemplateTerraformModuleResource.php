<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TemplateTerraformModuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                      => $this->id,
            'provider_configuration_id' => $this->provider_configuration_id,
            'module_slug'             => $this->module_slug,
            'is_built_in'             => $this->isBuiltIn(),
            'has_custom_hcl'          => $this->hasCustomHcl(),
            'main_tf'                 => $this->main_tf,
            'variables_tf'            => $this->variables_tf,
            'outputs_tf'              => $this->outputs_tf,
            'tfvars_mapping_json'     => $this->tfvars_mapping_json,
            'outputs_mapping_json'    => $this->outputs_mapping_json,
            'credential_env_keys'     => $this->credential_env_keys ?? [],
            'created_at'              => $this->created_at,
            'updated_at'              => $this->updated_at,
        ];
    }
}
