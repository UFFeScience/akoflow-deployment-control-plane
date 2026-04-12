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

data "google_client_config" "current" {}
data "google_project" "current" {
  project_id = data.google_client_config.current.project
}

data "google_project_service" "container_api" {
  project = data.google_client_config.current.project
  service = "container.googleapis.com"
}

output "project_id" {
  value = data.google_client_config.current.project
}

output "project_number" {
  value = data.google_project.current.number
}

output "container_api_service_id" {
  value = data.google_project_service.container_api.id
}
HCL;
    }
}
