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
 * Field keys are uppercased unless overridden by ENV_KEY_OVERRIDES:
 *   field_key "aws_access_key_id"   → env key "AWS_ACCESS_KEY_ID"
 *   field_key "service_account_json" → env key "GOOGLE_CREDENTIALS"
 *
 * When a deployment has more than one provider (e.g. AWS + GCP), all env vars
 * from every credential are merged. Later entries overwrite earlier ones on
 * key collision, so ordering matters.
 */
class ProviderCredentialResolverService
{
    /**
     * Maps stored field_key values to the exact env var name expected by the
     * corresponding Terraform provider (when the default strtoupper() would
     * produce the wrong name).
     */
    private const ENV_KEY_OVERRIDES = [
        'service_account_json' => 'GOOGLE_CREDENTIALS',
        'gcp_project_id'       => 'GOOGLE_PROJECT',
        'gcp_region'           => 'GOOGLE_REGION',
    ];
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
                $rawKey = $value->field_key;
                $envKey = self::ENV_KEY_OVERRIDES[$rawKey] ?? strtoupper($rawKey);
                $env[$envKey] = (string) $value->field_value;
            }
        }

        return $env;
    }
}
