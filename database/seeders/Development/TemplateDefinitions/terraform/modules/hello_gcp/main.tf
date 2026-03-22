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
  resource_name      = var.environment_id != "" ? "env-${var.environment_id}-${var.instance_name}" : var.instance_name
  ingress_port_range = var.ingress_from_port == var.ingress_to_port ? tostring(var.ingress_from_port) : "${var.ingress_from_port}-${var.ingress_to_port}"
  resolved_image     = var.image_gcp != "" ? var.image_gcp : data.google_compute_image.default.self_link
}

resource "google_compute_firewall" "hello" {
  name    = "${local.resource_name}-fw"
  network = var.network_gcp

  allow {
    protocol = var.ingress_protocol
    ports    = [local.ingress_port_range]
  }

  direction     = "INGRESS"
  priority      = 1000
  source_ranges = [var.ingress_cidr]
}

resource "google_compute_instance" "hello" {
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

  metadata_startup_script = var.user_data != "" ? var.user_data : null

  tags = ["${local.resource_name}"]
}

data "google_compute_image" "default" {
  family  = var.image_family_gcp
  project = var.image_project_gcp
}

