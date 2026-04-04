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

# Calls Resource Manager API (projects.get) — requires resourcemanager.projects.get on the project
data "google_project" "current" {}

output "project_id" {
  value = data.google_project.current.project_id
}

output "project_number" {
  value = data.google_project.current.number
}
HCL;
    }
}
