terraform {
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = ">= 5.0"
    }
  }
}

provider "google" {
  project = var.project_id
  region  = var.region
  zone    = var.zone
}

locals {
  resource_name  = var.environment_id != "" ? "env-${var.environment_id}-nginx" : "micro-nginx"
  resolved_image = var.image_gcp != "" ? var.image_gcp : data.google_compute_image.default.self_link

  startup_script = <<-EOF
    #!/bin/bash
    set -eux

    # Install Docker
    apt-get update -y
    apt-get install -y docker.io

    systemctl enable docker
    systemctl start docker

    # Run NGINX on the configured port
    docker run -d --name nginx --restart always -p ${var.nginx_port}:80 nginx:latest
  EOF
}

resource "google_compute_firewall" "nginx" {
  name    = "${local.resource_name}-fw"
  network = var.network_gcp

  allow {
    protocol = "tcp"
    ports    = [tostring(var.nginx_port)]
  }

  direction     = "INGRESS"
  priority      = 1000
  source_ranges = ["0.0.0.0/0"]
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

  metadata_startup_script = local.startup_script

  tags = [local.resource_name]
}

data "google_compute_image" "default" {
  family  = var.image_family_gcp
  project = var.image_project_gcp
}
