<?php

namespace Database\Seeders\Development\ProviderCredentials;

class AwsTemplate
{
    public static function get(): string
    {
        return <<<'HCL'
terraform {
  required_version = ">= 1.5"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

# Credentials injected via env: AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION
provider "aws" {}

# Calls AWS STS to verify the credentials have valid API access
data "aws_caller_identity" "current" {}

output "account_id" {
  value = data.aws_caller_identity.current.account_id
}

output "user_id" {
  value = data.aws_caller_identity.current.user_id
}
HCL;
    }
}
