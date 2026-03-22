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

resource "google_compute_firewall" "hello" {
  name    = "env-${var.environment_id}-${var.instance_name}-http"
  network = "default"

  allow {
    protocol = "tcp"
    ports    = ["80"]
  }

  direction     = "INGRESS"
  priority      = 1000
  source_ranges = ["0.0.0.0/0"]
}

resource "google_compute_instance" "hello" {
  name         = "env-${var.environment_id}-${var.instance_name}"
  machine_type = var.machine_type

  boot_disk {
    initialize_params {
      image = var.image != "" ? var.image : data.google_compute_image.ubuntu.self_link
    }
  }

  network_interface {
    network = "default"
    access_config {}
  }

  metadata_startup_script = <<-EOF
    #!/bin/bash
    apt-get update -y
    apt-get install -y docker.io
    systemctl enable docker
    systemctl start docker
    ${var.startup_script != "" ? var.startup_script : "docker run -d --name hello --restart always -p 80:80 ${var.docker_image} ${var.docker_args}"}
  EOF
}

data "google_compute_image" "ubuntu" {
  family  = "ubuntu-2204-lts"
  project = "ubuntu-os-cloud"
}

output "public_ip" {
  value = google_compute_instance.hello.network_interface[0].access_config[0].nat_ip
}

output "instance_name" {
  value = google_compute_instance.hello.name
}
