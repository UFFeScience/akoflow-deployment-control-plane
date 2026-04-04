<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnsiblePlaybookTaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'ansible_playbook_id' => $this->ansible_playbook_id,
            'runbook_id'          => $this->runbook_id,
            'position'            => $this->position,
            'name'                => $this->name,
            'module'              => $this->module,
            'module_args_json'    => $this->module_args_json,
            'when_condition'      => $this->when_condition,
            'become'              => $this->become,
            'tags_json'           => $this->tags_json ?? [],
            'enabled'             => $this->enabled,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
