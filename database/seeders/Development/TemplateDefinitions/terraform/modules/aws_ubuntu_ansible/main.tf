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
  resource_name = var.environment_id != "" ? "env-${var.environment_id}-docker" : "aws-docker-ansible"
  resolved_ami  = var.ami_id != "" ? var.ami_id : data.aws_ami.ubuntu.id
}

resource "aws_security_group" "docker_host" {
  name_prefix = "${local.resource_name}-"
  description = "SSH access for Ansible configuration"
  vpc_id      = var.vpc_id != "" ? var.vpc_id : null

  ingress {
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
    description = "SSH - required for Ansible to configure Docker"
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

resource "aws_instance" "docker_host" {
  ami                         = local.resolved_ami
  instance_type               = var.instance_type
  vpc_security_group_ids      = [aws_security_group.docker_host.id]
  associate_public_ip_address = true
  availability_zone           = local.az
  subnet_id                   = var.subnet_id != "" ? var.subnet_id : null
  key_name                    = var.key_name != "" ? var.key_name : null

  tags = {
    Name        = local.resource_name
    Environment = var.environment_id
  }
}

data "aws_ami" "ubuntu" {
  most_recent = true

  filter {
    name   = "name"
    values = ["ubuntu/images/hvm-ssd/ubuntu-*-22.04-amd64-server-*"]
  }

  filter {
    name   = "virtualization-type"
    values = ["hvm"]
  }

  owners = ["099720109477"]
}
