<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TemplateAnsiblePlaybookResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                       => $this->id,
            'provider_configuration_id'=> $this->provider_configuration_id,
            'playbook_slug'            => $this->playbook_slug,
            'is_built_in'              => $this->isBuiltIn(),
            'has_custom_playbook'      => $this->hasCustomPlaybook(),
            'playbook_yaml'            => $this->playbook_yaml,
            'inventory_template'       => $this->inventory_template,
            'vars_mapping_json'        => $this->vars_mapping_json,
            'outputs_mapping_json'     => $this->outputs_mapping_json,
            'credential_env_keys'      => $this->credential_env_keys ?? [],
            'roles_json'               => $this->roles_json ?? [],
            'created_at'               => $this->created_at,
            'updated_at'               => $this->updated_at,
        ];
    }
}
