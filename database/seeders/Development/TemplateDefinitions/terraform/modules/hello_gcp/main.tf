terraform {
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = ">= 5.0"
    }
  }
}

provider "google" {
  project = var.project_id != "" ? var.project_id : null
  region  = var.region != "" ? var.region : null
  zone    = var.zone != "" ? var.zone : null
}

locals {
  resource_name  = var.environment_id != "" ? "env-${var.environment_id}-nginx" : "micro-nginx"
  resolved_image = var.image_gcp != "" ? var.image_gcp : data.google_compute_image.default.self_link
  ssh_key_entry  = trimspace(var.ssh_public_key) == "" ? "" : (length(regexall("^[^:]+:", trimspace(var.ssh_public_key))) > 0 ? trimspace(var.ssh_public_key) : "${var.ssh_user}:${trimspace(var.ssh_public_key)}")

  startup_script = <<-EOF
    #!/bin/bash
    set -eux

    # Keep bootstrap minimal. Docker + NGINX are configured by after_provision Ansible playbooks.
    echo "akocloud bootstrap ready" > /var/log/akocloud-bootstrap.log
  EOF
}

resource "google_compute_firewall" "nginx" {
  name    = "${local.resource_name}-fw"
  network = var.network_gcp

  allow {
    protocol = "tcp"
    # Always open port 80; also open nginx_port when it differs from 80
    ports = distinct(["80", tostring(var.nginx_port)])
  }

  direction     = "INGRESS"
  priority      = 1000
  source_ranges = ["0.0.0.0/0"]
  target_tags   = [local.resource_name]
}

resource "google_compute_firewall" "ssh" {
  count   = 1
  name    = "${local.resource_name}-ssh-fw"
  network = var.network_gcp

  allow {
    protocol = "tcp"
    ports    = ["22"]
  }

  direction     = "INGRESS"
  priority      = 1000
  source_ranges = ["0.0.0.0/0"]
  target_tags   = [local.resource_name]
}

resource "google_compute_instance" "nginx" {
  name         = local.resource_name
  machine_type = var.machine_type
  zone         = var.zone

  boot_disk {
    initialize_params {
      image = local.resolved_image
    }
  }

  network_interface {
    network = var.network_gcp
    access_config {}
  }

  metadata = var.ssh_public_key != "" ? { "ssh-keys" = local.ssh_key_entry } : {}

  metadata_startup_script = local.startup_script

  tags = [local.resource_name]
}

data "google_compute_image" "default" {
  family  = var.image_family_gcp
  project = var.image_project_gcp
}
