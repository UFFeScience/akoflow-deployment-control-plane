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

    # Run NGINX on the configured port
    docker run -d --name nginx --restart always -p ${var.nginx_port}:80 nginx:latest
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
