<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnsiblePlaybookRunResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'deployment_id'  => $this->deployment_id,
            'playbook_id'    => $this->playbook_id ?? $this->activity_id,
            'playbook_name'  => $this->playbook_name ?? $this->activity_name,
            'activity_id'    => $this->activity_id ?? $this->playbook_id,
            'activity_name'  => $this->activity_name ?? $this->playbook_name,
            'trigger'        => $this->trigger,
            'status'         => $this->status,
            'provider_type'  => $this->provider_type,
            'triggered_by'   => $this->triggered_by,
            'workspace_path' => $this->workspace_path,
            'extra_vars'     => $this->extra_vars_json,
            'output'         => $this->output_json,
            'task_host_statuses' => AnsiblePlaybookRunTaskHostResource::collection($this->whenLoaded('taskHostStatuses')),
            'started_at'     => $this->started_at,
            'finished_at'    => $this->finished_at,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
