<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TerraformRunResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'environment_id'  => $this->environment_id,
            'action'         => $this->action,
            'status'         => $this->status,
            'provider_type'  => $this->provider_type,
            'workspace_path' => $this->workspace_path,
            'tfvars'         => $this->tfvars_json,
            'output'         => $this->output_json,
            'started_at'     => $this->started_at,
            'finished_at'    => $this->finished_at,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
            // logs only included when explicitly requested (list excludes them)
            'logs'           => $this->when($request->boolean('with_logs'), $this->logs),
        ];
    }
}
