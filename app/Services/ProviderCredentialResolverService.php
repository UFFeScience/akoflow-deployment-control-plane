<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\ProviderCredential;
use RuntimeException;

/**
 * Loads all credentials attached to a Deployment (via the
 * deployment_provider_credentials pivot) and merges their field values into a
 * single environment-variable map ready to be injected into a Terraform process.
 *
 * Field keys are uppercased:
 *   field_key "aws_access_key_id" → env key "AWS_ACCESS_KEY_ID"
 *
 * When a deployment has more than one provider (e.g. AWS + GCP), all env vars
 * from every credential are merged. Later entries overwrite earlier ones on
 * key collision, so ordering matters.
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
        $pivotRecords = $deployment->providerCredentials()
            ->whereNotNull('provider_credential_id')
            ->get();

        if ($pivotRecords->isEmpty()) {
            throw new RuntimeException(
                "Deployment [{$deployment->id}] has no provider credentials configured."
            );
        }

        $env = [];
        foreach ($pivotRecords as $pivot) {
            $credential = ProviderCredential::with('values')
                ->find($pivot->provider_credential_id);

            if (!$credential) {
                continue;
            }

            foreach ($credential->values as $value) {
                $env[strtoupper($value->field_key)] = (string) $value->field_value;
            }
        }

        return $env;
    }
}
