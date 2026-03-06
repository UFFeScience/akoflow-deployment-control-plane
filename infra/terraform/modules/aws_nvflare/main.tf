terraform {
  required_version = ">= 1.5"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region
}

locals {
  common_tags = {
    ManagedBy    = "akocloud-terraform"
    ExperimentId = var.experiment_id
    NVFlareVersion = var.nvflare_version
  }
}

# ── VPC ───────────────────────────────────────────────────────────────────────
resource "aws_vpc" "main" {
  cidr_block           = var.vpc_cidr
  enable_dns_support   = true
  enable_dns_hostnames = true

  tags = merge(local.common_tags, { Name = "nvflare-vpc-${var.experiment_id}" })
}

resource "aws_internet_gateway" "igw" {
  vpc_id = aws_vpc.main.id
  tags   = merge(local.common_tags, { Name = "nvflare-igw-${var.experiment_id}" })
}

resource "aws_subnet" "public" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = cidrsubnet(var.vpc_cidr, 4, 0)
  map_public_ip_on_launch = true
  availability_zone       = "${var.aws_region}a"

  tags = merge(local.common_tags, { Name = "nvflare-subnet-public-${var.experiment_id}" })
}

resource "aws_route_table" "public" {
  vpc_id = aws_vpc.main.id
  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.igw.id
  }
  tags = merge(local.common_tags, { Name = "nvflare-rt-${var.experiment_id}" })
}

resource "aws_route_table_association" "public" {
  subnet_id      = aws_subnet.public.id
  route_table_id = aws_route_table.public.id
}

# ── Security Group ────────────────────────────────────────────────────────────
resource "aws_security_group" "nvflare" {
  name        = "nvflare-sg-${var.experiment_id}"
  description = "NVFlare federated learning ports"
  vpc_id      = aws_vpc.main.id

  ingress {
    from_port   = var.server_port
    to_port     = var.server_port
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
    description = "NVFlare server"
  }

  ingress {
    from_port   = var.admin_port
    to_port     = var.admin_port
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
    description = "NVFlare admin"
  }

  ingress {
    from_port   = var.overseer_port
    to_port     = var.overseer_port
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
    description = "NVFlare overseer"
  }

  ingress {
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
    description = "SSH"
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = local.common_tags
}

# ── Data source: latest Amazon Linux 2023 AMI ─────────────────────────────────
data "aws_ami" "al2023" {
  most_recent = true
  owners      = ["amazon"]
  filter {
    name   = "name"
    values = ["al2023-ami-*-x86_64"]
  }
}

# ── Userdata template ─────────────────────────────────────────────────────────
locals {
  userdata_base = <<-EOF
    #!/bin/bash
    yum update -y
    yum install -y docker
    systemctl enable docker
    systemctl start docker
  EOF
}

# ── nvflare-server ────────────────────────────────────────────────────────────
resource "aws_instance" "server" {
  ami                    = data.aws_ami.al2023.id
  instance_type          = var.server_instance_type
  subnet_id              = aws_subnet.public.id
  vpc_security_group_ids = [aws_security_group.nvflare.id]
  key_name               = var.key_name != "" ? var.key_name : null

  user_data = <<-EOF
    ${local.userdata_base}
    mkdir -p ${var.server_workspace_dir}
    docker pull ${var.server_docker_image}
    ${var.server_docker_command}
  EOF

  root_block_device {
    volume_size = var.server_disk_size_gb
    volume_type = "gp3"
    encrypted   = true
  }

  tags = merge(local.common_tags, { Name = "nvflare-server-${var.experiment_id}", Role = "nvflare-server" })
}

# ── nvflare-overseer ──────────────────────────────────────────────────────────
resource "aws_instance" "overseer" {
  ami                    = data.aws_ami.al2023.id
  instance_type          = var.overseer_instance_type
  subnet_id              = aws_subnet.public.id
  vpc_security_group_ids = [aws_security_group.nvflare.id]
  key_name               = var.key_name != "" ? var.key_name : null

  user_data = <<-EOF
    ${local.userdata_base}
    mkdir -p ${var.overseer_workspace_dir}
    docker pull ${var.overseer_docker_image}
    ${var.overseer_docker_command}
  EOF

  root_block_device {
    volume_size = var.overseer_disk_size_gb
    volume_type = "gp3"
    encrypted   = true
  }

  tags = merge(local.common_tags, { Name = "nvflare-overseer-${var.experiment_id}", Role = "nvflare-overseer" })
}

# ── nvflare-dfanalyse ─────────────────────────────────────────────────────────
resource "aws_instance" "dfanalyse" {
  ami                    = data.aws_ami.al2023.id
  instance_type          = var.dfanalyse_instance_type
  subnet_id              = aws_subnet.public.id
  vpc_security_group_ids = [aws_security_group.nvflare.id]
  key_name               = var.key_name != "" ? var.key_name : null

  user_data = <<-EOF
    ${local.userdata_base}
    mkdir -p ${var.dfanalyse_workspace_dir}
    docker pull ${var.dfanalyse_docker_image}
    ${var.dfanalyse_docker_command}
  EOF

  root_block_device {
    volume_size = var.dfanalyse_disk_size_gb
    volume_type = "gp3"
    encrypted   = true
  }

  tags = merge(local.common_tags, { Name = "nvflare-dfanalyse-${var.experiment_id}", Role = "nvflare-dfanalyse" })
}

# ── nvflare-site ──────────────────────────────────────────────────────────────
resource "aws_instance" "site" {
  ami                    = data.aws_ami.al2023.id
  instance_type          = var.site_instance_type
  subnet_id              = aws_subnet.public.id
  vpc_security_group_ids = [aws_security_group.nvflare.id]
  key_name               = var.key_name != "" ? var.key_name : null

  user_data = <<-EOF
    ${local.userdata_base}
    mkdir -p ${var.site_workspace_dir}
    mkdir -p ${var.site_local_dataset_path}
    docker pull ${var.site_docker_image}
    ${var.site_docker_command}
  EOF

  root_block_device {
    volume_size = var.site_disk_size_gb
    volume_type = "gp3"
    encrypted   = true
  }

  tags = merge(local.common_tags, { Name = "nvflare-site-${var.experiment_id}", Role = "nvflare-site" })
}
