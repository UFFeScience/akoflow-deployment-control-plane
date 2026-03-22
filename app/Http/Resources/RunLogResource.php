<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RunLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                      => $this->id,
            'terraform_run_id'        => $this->terraform_run_id,
            'provisioned_resource_id' => $this->provisioned_resource_id,
            'environment_id'          => $this->environment_id,
            'source'                  => $this->source,
            'level'                   => $this->level,
            'message'                 => $this->message,
            'created_at'              => $this->created_at,
        ];
    }
}
