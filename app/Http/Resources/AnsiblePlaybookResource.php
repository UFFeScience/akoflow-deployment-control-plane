<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnsiblePlaybookResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                        => $this->id,
            'provider_configuration_id' => $this->provider_configuration_id,
            'name'                      => $this->name,
            'description'               => $this->description,
            'trigger'                   => $this->trigger,
            'playbook_slug'             => $this->playbook_slug,
            'playbook_yaml'             => $this->playbook_yaml,
            'inventory_template'        => $this->inventory_template,
            'vars_mapping_json'         => $this->vars_mapping_json,
            'outputs_mapping_json'      => $this->outputs_mapping_json,
            'credential_env_keys'       => $this->credential_env_keys ?? [],
            'roles_json'                => $this->roles_json ?? [],
            'position'                  => $this->position,
            'enabled'                   => $this->enabled,
            'activity_id'               => $this->id,
            'activity_name'             => $this->name,
            'tasks'                     => AnsiblePlaybookTaskResource::collection(
                $this->whenLoaded('tasks'),
            ),
            'dependencies'              => AnsiblePlaybookResource::collection(
                $this->whenLoaded('dependencies'),
            ),
            'created_at'                => $this->created_at,
            'updated_at'                => $this->updated_at,
        ];
    }
}
