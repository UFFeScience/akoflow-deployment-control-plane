<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TemplateTerraformModuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'template_version_id' => $this->template_version_id,
            'module_slug'         => $this->module_slug,
            'provider_type'       => $this->provider_type,
            'is_built_in'         => $this->isBuiltIn(),
            'has_custom_hcl'      => $this->hasCustomHcl(),
            // HCL files only included when showing a single resource
            'main_tf'             => $this->when($request->routeIs('*.show'), $this->main_tf),
            'variables_tf'        => $this->when($request->routeIs('*.show'), $this->variables_tf),
            'outputs_tf'          => $this->when($request->routeIs('*.show'), $this->outputs_tf),
            'tfvars_mapping_json'   => $this->tfvars_mapping_json,
            'outputs_mapping_json'  => $this->outputs_mapping_json,
            'credential_env_keys'   => $this->credential_env_keys ?? [],
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
