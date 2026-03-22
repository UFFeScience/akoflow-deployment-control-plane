<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\ProviderCredential;
use RuntimeException;

/**
 * Loads the credential defined on a Deployment and returns all its field
 * values as an environment-variable map ready to be injected into a
 * Terraform process environment.
 *
 * Field keys are uppercased:
 *   field_key "aws_access_key_id" → env key "AWS_ACCESS_KEY_ID"
 */
class ProviderCredentialResolverService
{
    /**
     * @return array<string, string>  ['ENV_VAR_NAME' => 'value', ...]
     *
     * @throws RuntimeException when the deployment has no credential configured.
     */
    public function resolve(Deployment $deployment): array
    {
        if (!$deployment->provider_credential_id) {
            throw new RuntimeException(
                "Deployment [{$deployment->id}] has no provider credential configured."
            );
        }

        /** @var ProviderCredential|null $credential */
        $credential = ProviderCredential::with('values')
            ->find($deployment->provider_credential_id);

        if (!$credential) {
            throw new RuntimeException(
                "Provider credential [{$deployment->provider_credential_id}] not found "
                . "(referenced by Deployment [{$deployment->id}])."
            );
        }

        $env = [];
        foreach ($credential->values as $value) {
            $env[strtoupper($value->field_key)] = (string) $value->field_value;
        }

        return $env;
    }
}
