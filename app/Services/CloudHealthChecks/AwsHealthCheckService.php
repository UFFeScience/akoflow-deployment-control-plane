<?php

namespace App\Services\CloudHealthChecks;

class AwsHealthCheckService
{
    /**
     * Build a minimal Terraform workspace that calls aws_caller_identity (STS REST API).
     *
     * Credentials are passed as environment variables consumed by the AWS Terraform provider:
     *   AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION
     *
     * @param  array<string, string>  $credentialValues  field_key => field_value from ProviderCredentialValue
     * @return array{main_tf: string, env: array<string, string>, tfvars: array}
     */
    public function buildWorkspace(array $credentialValues): array
    {
        $accessKeyId     = $credentialValues['aws_access_key_id'] ?? '';
        $secretAccessKey = $credentialValues['aws_secret_access_key'] ?? '';
        $region          = $credentialValues['aws_region'] ?? 'us-east-1';

        if (empty($accessKeyId) || empty($secretAccessKey)) {
            throw new \InvalidArgumentException(
                'Missing required AWS credentials: aws_access_key_id or aws_secret_access_key.',
            );
        }

        $mainTf = <<<'TF'
terraform {
  required_version = ">= 1.5"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

# Credentials are injected via environment variables:
# AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION
provider "aws" {}

# Calls the AWS STS REST API to validate credentials
data "aws_caller_identity" "current" {}

output "account_id" {
  value = data.aws_caller_identity.current.account_id
}

output "user_id" {
  value = data.aws_caller_identity.current.user_id
}
TF;

        return [
            'main_tf' => $mainTf,
            'env'     => [
                'AWS_ACCESS_KEY_ID'     => $accessKeyId,
                'AWS_SECRET_ACCESS_KEY' => $secretAccessKey,
                'AWS_DEFAULT_REGION'    => $region,
            ],
            'tfvars' => [],
        ];
    }
}
