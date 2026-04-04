<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnsibleTaskRunResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'ansible_run_id'   => $this->ansible_run_id,
            'runbook_run_id'   => $this->runbook_run_id,
            'playbook_task_id' => $this->playbook_task_id,
            'task_name'        => $this->task_name,
            'module'           => $this->module,
            'position'         => $this->position,
            'status'           => $this->status,
            'output'           => $this->output,
            'started_at'       => $this->started_at,
            'finished_at'      => $this->finished_at,
            'created_at'       => $this->created_at,
        ];
    }
}
