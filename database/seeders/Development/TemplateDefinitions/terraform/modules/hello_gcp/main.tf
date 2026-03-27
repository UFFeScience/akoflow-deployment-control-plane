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

  startup_script = <<-EOF
    #!/bin/bash
    set -eux

    # Install Docker
    apt-get update -y
    apt-get install -y docker.io

    systemctl enable docker
    systemctl start docker

    # Create directories for NGINX config and HTML content
    mkdir -p /opt/nginx/html

    # Write nginx.conf with user-defined settings
    cat > /opt/nginx/nginx.conf <<'NGINXEOF'
user nginx;
worker_processes ${var.nginx_worker_processes};

events {
    worker_connections ${var.nginx_worker_connections};
}

http {
    include       /etc/nginx/mime.types;
    default_type  application/octet-stream;

    sendfile        on;
    keepalive_timeout ${var.nginx_keepalive_timeout};
    client_max_body_size ${var.nginx_client_max_body_size};

    gzip ${var.nginx_gzip};

    server {
        listen 80;
        server_name ${var.nginx_server_name};

        root /usr/share/nginx/html;
        index index.html;

        location / {
            try_files $uri $uri/ =404;
        }
    }
}
NGINXEOF

    # Write default index.html
    cat > /opt/nginx/html/index.html <<'HTMLEOF'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>${var.html_page_title}</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: #f0f2f5;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .card {
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10);
      padding: 3rem 4rem;
      text-align: center;
      max-width: 520px;
      width: 90%;
    }
    .card h1 { font-size: 2rem; color: #1a1a2e; margin-bottom: 1rem; }
    .card p  { font-size: 1.1rem; color: #555; line-height: 1.6; }
    .badge {
      display: inline-block;
      margin-top: 1.5rem;
      padding: 0.3rem 0.9rem;
      border-radius: 999px;
      background: #e8f5e9;
      color: #2e7d32;
      font-size: 0.85rem;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>${var.html_page_title}</h1>
    <p>${var.html_page_body}</p>
    <span class="badge">&#10003; NGINX is running</span>
  </div>
</body>
</html>
HTMLEOF

    # Run NGINX mounting the custom config and HTML directory
    docker run -d --name nginx --restart always \
      -p ${var.nginx_port}:80 \
      -v /opt/nginx/nginx.conf:/etc/nginx/nginx.conf:ro \
      -v /opt/nginx/html:/usr/share/nginx/html:ro \
      nginx:latest
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
  count   = var.ssh_public_key != "" ? 1 : 0
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

  metadata = var.ssh_public_key != "" ? { "ssh-keys" = var.ssh_public_key } : {}

  metadata_startup_script = local.startup_script

  tags = [local.resource_name]
}

data "google_compute_image" "default" {
  family  = var.image_family_gcp
  project = var.image_project_gcp
}
