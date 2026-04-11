<?php

namespace Database\Seeders\Development\ProviderCredentials;

class GcpTemplate
{
    public static function get(): string
    {
        return <<<'HCL'
terraform {
  required_version = ">= 1.5"
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = "~> 5.0"
    }
  }
}

# Credentials injected via env: GOOGLE_CREDENTIALS, GOOGLE_PROJECT, GOOGLE_REGION
provider "google" {}

# Reads OAuth2 token from the configured credentials — no IAM permissions required
data "google_client_config" "current" {}

output "project_id" {
  value = data.google_client_config.current.project
}

output "access_token" {
  value     = data.google_client_config.current.access_token
  sensitive = true
}
HCL;
    }
}
