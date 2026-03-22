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
  resource_name = var.environment_id != "" ? "env-${var.environment_id}-${var.instance_name}" : var.instance_name
  resolved_ami  = var.ami_id != "" ? var.ami_id : data.aws_ami.default.id
}

resource "aws_security_group" "hello" {
  name_prefix = "${local.resource_name}-"
  description = "Ingress ${var.ingress_from_port}-${var.ingress_to_port}/${var.ingress_protocol} from ${var.ingress_cidr}"
  vpc_id      = var.vpc_id != "" ? var.vpc_id : null

  ingress {
    from_port   = var.ingress_from_port
    to_port     = var.ingress_to_port
    protocol    = var.ingress_protocol
    cidr_blocks = [var.ingress_cidr]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = [var.egress_cidr]
  }

  tags = {
    Name        = "${local.resource_name}-sg"
    Environment = var.environment_id
  }

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_instance" "hello" {
  ami                         = local.resolved_ami
  instance_type               = var.instance_type
  vpc_security_group_ids      = [aws_security_group.hello.id]
  associate_public_ip_address = var.associate_public_ip
  availability_zone           = local.az
  subnet_id                   = var.subnet_id != "" ? var.subnet_id : null
  user_data                   = var.user_data != "" ? var.user_data : null

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
