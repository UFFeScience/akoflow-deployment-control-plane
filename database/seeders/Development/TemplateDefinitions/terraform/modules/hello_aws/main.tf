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
  az = var.zone != "" ? var.zone : "${var.region}a"
}

resource "aws_security_group" "hello" {
  name        = "${var.instance_name}-sg"
  description = "Allow HTTP access"

  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

resource "aws_instance" "hello" {
  ami                         = var.ami_id != "" ? var.ami_id : data.aws_ami.amazon_linux.id
  instance_type               = var.instance_type
  vpc_security_group_ids      = [aws_security_group.hello.id]
  associate_public_ip_address = true
  availability_zone           = local.az
  tags = {
    Name = var.instance_name
  }

  user_data = <<-EOF
              #!/bin/bash
              yum update -y
              amazon-linux-extras install docker -y || yum install docker -y
              systemctl enable docker
              systemctl start docker
              ${var.startup_script != "" ? var.startup_script : "docker run -d --name hello --restart always -p 80:80 ${var.docker_image} ${var.docker_args}"}
              EOF
}

data "aws_ami" "amazon_linux" {
  most_recent = true

  filter {
    name   = "name"
    values = ["amzn2-ami-hvm-*-x86_64-gp2"]
  }

  owners = ["amazon"]
}

output "public_ip" {
  value = aws_instance.hello.public_ip
}

output "instance_id" {
  value = aws_instance.hello.id
}
