<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RunbookRunResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'deployment_id'  => $this->deployment_id,
            'runbook_id'     => $this->runbook_id,
            'runbook_name'   => $this->runbook_name,
            'status'         => $this->status,
            'provider_type'  => $this->provider_type,
            'triggered_by'   => $this->triggered_by,
            'workspace_path' => $this->workspace_path,
            'extra_vars'     => $this->extra_vars_json,
            'output'         => $this->output_json,
            'task_runs'      => AnsibleTaskRunResource::collection(
                $this->whenLoaded('taskRuns'),
            ),
            'started_at'     => $this->started_at,
            'finished_at'    => $this->finished_at,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
