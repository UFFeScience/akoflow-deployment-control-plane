<?php

namespace Database\Seeders\Development\ProviderCredentials;

class LocalTemplate
{
    public static function get(): string
    {
        return <<<'HCL'
terraform {
  required_version = ">= 1.5"
  required_providers {
    null = {
      source  = "hashicorp/null"
      version = "~> 3.0"
    }
  }
}

variable "host" {}
variable "user" {}

variable "ssh_password" {
  default   = ""
  sensitive = true
}

variable "ssh_private_key" {
  default   = ""
  sensitive = true
}

# Connects via SSH (password or private key) and runs basic commands to verify the host is reachable
resource "null_resource" "health_check" {
  provisioner "remote-exec" {
    connection {
      type        = "ssh"
      host        = var.host
      user        = var.user
      password    = var.ssh_password != "" ? var.ssh_password : null
      private_key = var.ssh_private_key != "" ? var.ssh_private_key : null
    }

    inline = [
      "echo 'Connected to host'",
      "hostname",
      "docker ps",
      "kubectl get pods || true",
    ]
  }
}
HCL;
    }
}
