<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnsiblePlaybookRunTaskHostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                      => $this->id,
            'ansible_playbook_run_id' => $this->ansible_playbook_run_id,
            'ansible_playbook_task_id'=> $this->ansible_playbook_task_id,
            'host'                    => $this->host,
            'task_name'               => $this->task_name,
            'module'                  => $this->module,
            'position'                => $this->position,
            'status'                  => $this->status,
            'output'                  => $this->output,
            'started_at'              => $this->started_at,
            'finished_at'             => $this->finished_at,
            'created_at'              => $this->created_at,
            'updated_at'              => $this->updated_at,
        ];
    }
}
