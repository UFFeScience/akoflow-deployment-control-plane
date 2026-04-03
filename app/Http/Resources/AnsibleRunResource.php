<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnsibleRunResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'deployment_id'   => $this->deployment_id,
            'action'          => $this->action,
            'status'          => $this->status,
            'provider_type'   => $this->provider_type,
            'workspace_path'  => $this->workspace_path,
            'extra_vars'      => $this->extra_vars_json,
            'output'          => $this->output_json,
            'started_at'      => $this->started_at,
            'finished_at'     => $this->finished_at,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
