<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RunbookResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                        => $this->id,
            'provider_configuration_id' => $this->provider_configuration_id,
            'name'                      => $this->name,
            'description'               => $this->description,
            'playbook_yaml'             => $this->playbook_yaml,
            'vars_mapping_json'         => $this->vars_mapping_json,
            'credential_env_keys'       => $this->credential_env_keys ?? [],
            'roles_json'                => $this->roles_json ?? [],
            'position'                  => $this->position,
            'tasks'                     => AnsiblePlaybookTaskResource::collection(
                $this->whenLoaded('tasks'),
            ),
            'created_at'                => $this->created_at,
            'updated_at'                => $this->updated_at,
        ];
    }
}
