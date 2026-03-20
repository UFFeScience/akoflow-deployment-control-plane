<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\ProviderCredential;
use RuntimeException;

/**
 * Loads the active credential for a Provider from the database and returns
 * all its field values as an environment-variable map.
 *
 * Field keys are uppercased so they are ready to be injected directly into
 * a Terraform process environment:
 *   field_key "aws_access_key_id" → env key "AWS_ACCESS_KEY_ID"
 */
class ProviderCredentialResolverService
{
    /**
     * @return array<string, string>  ['ENV_VAR_NAME' => 'value', ...]
     *
     * @throws RuntimeException when no active credential exists for the provider.
     */
    public function resolve(Provider $provider): array
    {
        /** @var ProviderCredential|null $credential */
        $credential = $provider->credentials()
            ->where('is_active', true)
            ->with('values')
            ->first();

        if (!$credential) {
            throw new RuntimeException(
                "Provider [{$provider->name}] has no active credential configured. " .
                'Create one via POST /organizations/{orgId}/providers/{providerId}/credentials.'
            );
        }

        $env = [];
        foreach ($credential->values as $value) {
            $env[strtoupper($value->field_key)] = (string) $value->field_value;
        }

        return $env;
    }
}
