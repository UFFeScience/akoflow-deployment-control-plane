<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderCredentialValueResource extends JsonResource
{
    /**
     * Field keys that contain secrets and must never be sent to the client.
     * Non-sensitive fields (host, user, region, project_id, etc.) are returned as-is.
     */
    private const SENSITIVE_KEYS = [
        'ssh_password',
        'ssh_private_key',
        'password',
        'private_key',
        'secret',
        'token',
        'access_key',
        'secret_key',
        'service_account_json',
        'aws_secret_access_key',
    ];

    public function toArray($request): array
    {
        $isSensitive = in_array($this->field_key, self::SENSITIVE_KEYS, true)
            || str_contains($this->field_key, 'password')
            || str_contains($this->field_key, 'secret')
            || str_contains($this->field_key, 'private_key')
            || str_contains($this->field_key, 'token');

        return [
            'id'                     => $this->id,
            'provider_credential_id' => $this->provider_credential_id,
            'field_key'              => $this->field_key,
            'field_value'            => $isSensitive ? null : $this->field_value,
        ];
    }
}
