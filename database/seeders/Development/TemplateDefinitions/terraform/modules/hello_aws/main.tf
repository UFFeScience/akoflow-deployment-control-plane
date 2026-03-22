terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = ">= 5.0"
    }
  }
}

provider "aws" {
  region = var.region
}

locals {
  az            = var.zone != "" ? var.zone : "${var.region}a"
  resource_name = var.environment_id != "" ? "env-${var.environment_id}-nginx" : "micro-nginx"
  resolved_ami  = var.ami_id != "" ? var.ami_id : data.aws_ami.default.id

  startup_script = <<-EOF
    #!/bin/bash
    set -eux

    # Install Docker
    if command -v apt-get &>/dev/null; then
      apt-get update -y
      apt-get install -y docker.io
    else
      yum update -y
      amazon-linux-extras install docker -y 2>/dev/null || yum install -y docker || true
    fi

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
            try_files $$uri $$uri/ =404;
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

resource "aws_security_group" "nginx" {
  name_prefix = "${local.resource_name}-"
  description = "Allow TCP traffic on port ${var.nginx_port}"
  vpc_id      = var.vpc_id != "" ? var.vpc_id : null

  ingress {
    from_port   = var.nginx_port
    to_port     = var.nginx_port
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name        = "${local.resource_name}-sg"
    Environment = var.environment_id
  }

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_instance" "nginx" {
  ami                         = local.resolved_ami
  instance_type               = var.instance_type
  vpc_security_group_ids      = [aws_security_group.nginx.id]
  associate_public_ip_address = true
  availability_zone           = local.az
  subnet_id                   = var.subnet_id != "" ? var.subnet_id : null
  user_data                   = local.startup_script

  tags = {
    Name        = local.resource_name
    Environment = var.environment_id
  }
}

data "aws_ami" "default" {
  most_recent = true

  filter {
    name   = "name"
    values = [var.ami_filter]
  }

  owners = [var.ami_owners]
}
