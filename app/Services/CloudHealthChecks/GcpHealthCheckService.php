<?php

namespace App\Services\CloudHealthChecks;

class GcpHealthCheckService
{
    /**
     * Build a minimal Terraform workspace that calls google_client_config (GCP REST API).
     *
     * Credentials are passed as environment variables consumed by the Google Terraform provider:
     *   GOOGLE_CREDENTIALS (service account JSON string), GOOGLE_PROJECT, GOOGLE_REGION
     *
     * @param  array<string, string>  $credentialValues  field_key => field_value from ProviderCredentialValue
     * @return array{main_tf: string, env: array<string, string>, tfvars: array}
     */
    public function buildWorkspace(array $credentialValues): array
    {
        $serviceAccountJson = $credentialValues['service_account_json'] ?? '';
        $projectId          = $credentialValues['gcp_project_id'] ?? '';
        $region             = $credentialValues['gcp_region'] ?? 'us-central1';

        if (empty($serviceAccountJson)) {
            throw new \InvalidArgumentException('Missing required GCP credential: service_account_json.');
        }

        $decoded = json_decode($serviceAccountJson, true);

        if (!is_array($decoded) || !isset($decoded['type'], $decoded['private_key'], $decoded['client_email'])) {
            throw new \InvalidArgumentException(
                'Invalid GCP service account JSON: missing required fields (type, private_key, client_email).',
            );
        }

        $mainTf = <<<'TF'
terraform {
  required_version = ">= 1.5"
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = "~> 5.0"
    }
  }
}

# Credentials are injected via environment variables:
# GOOGLE_CREDENTIALS (service account JSON), GOOGLE_PROJECT, GOOGLE_REGION
provider "google" {}

# Calls the GCP Resource Manager REST API to validate credentials
data "google_client_config" "current" {}

output "project" {
  value = data.google_client_config.current.project
}

output "region" {
  value = data.google_client_config.current.region
}
TF;

        return [
            'main_tf' => $mainTf,
            'env'     => [
                'GOOGLE_CREDENTIALS' => $serviceAccountJson,
                'GOOGLE_PROJECT'     => $projectId,
                'GOOGLE_REGION'      => $region,
            ],
            'tfvars' => [],
        ];
    }
}
