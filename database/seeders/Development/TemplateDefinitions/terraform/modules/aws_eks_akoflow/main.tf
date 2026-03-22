terraform {
  required_version = ">= 1.5"
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
  name        = var.environment_id != "" ? "akocloud-${var.environment_id}" : "akocloud-eks"
  eks_name    = "${local.name}-cluster"
  engine_name = "${local.name}-engine"

  common_tags = {
    ManagedBy     = "akocloud-terraform"
    EnvironmentId = var.environment_id
  }
}

# ── VPC ───────────────────────────────────────────────────────────────────────

resource "aws_vpc" "main" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_support   = true
  enable_dns_hostnames = true

  tags = merge(local.common_tags, { Name = "${local.name}-vpc" })
}

resource "aws_internet_gateway" "igw" {
  vpc_id = aws_vpc.main.id
  tags   = merge(local.common_tags, { Name = "${local.name}-igw" })
}

resource "aws_subnet" "public_a" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.1.0/24"
  availability_zone       = "${var.region}a"
  map_public_ip_on_launch = true

  tags = merge(local.common_tags, {
    Name                                      = "${local.name}-subnet-a"
    "kubernetes.io/cluster/${local.eks_name}" = "owned"
    "kubernetes.io/role/elb"                  = "1"
  })
}

resource "aws_subnet" "public_b" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.2.0/24"
  availability_zone       = "${var.region}b"
  map_public_ip_on_launch = true

  tags = merge(local.common_tags, {
    Name                                      = "${local.name}-subnet-b"
    "kubernetes.io/cluster/${local.eks_name}" = "owned"
    "kubernetes.io/role/elb"                  = "1"
  })
}

resource "aws_route_table" "public" {
  vpc_id = aws_vpc.main.id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.igw.id
  }

  tags = merge(local.common_tags, { Name = "${local.name}-rt" })
}

resource "aws_route_table_association" "a" {
  subnet_id      = aws_subnet.public_a.id
  route_table_id = aws_route_table.public.id
}

resource "aws_route_table_association" "b" {
  subnet_id      = aws_subnet.public_b.id
  route_table_id = aws_route_table.public.id
}

# ── IAM: EKS Cluster Role ─────────────────────────────────────────────────────

resource "aws_iam_role" "eks_cluster" {
  name = "${local.eks_name}-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action    = "sts:AssumeRole"
      Effect    = "Allow"
      Principal = { Service = "eks.amazonaws.com" }
    }]
  })

  tags = local.common_tags
}

resource "aws_iam_role_policy_attachment" "eks_cluster_policy" {
  role       = aws_iam_role.eks_cluster.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKSClusterPolicy"
}

# ── IAM: EKS Node Group Role ──────────────────────────────────────────────────

resource "aws_iam_role" "eks_node" {
  name = "${local.eks_name}-node-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action    = "sts:AssumeRole"
      Effect    = "Allow"
      Principal = { Service = "ec2.amazonaws.com" }
    }]
  })

  tags = local.common_tags
}

resource "aws_iam_role_policy_attachment" "eks_node_policy" {
  role       = aws_iam_role.eks_node.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKSWorkerNodePolicy"
}

resource "aws_iam_role_policy_attachment" "eks_cni_policy" {
  role       = aws_iam_role.eks_node.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKS_CNI_Policy"
}

resource "aws_iam_role_policy_attachment" "eks_ecr_readonly" {
  role       = aws_iam_role.eks_node.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonEC2ContainerRegistryReadOnly"
}

# ── IAM: AkoFlow Engine Role + Instance Profile ────────────────────────────────

resource "aws_iam_role" "engine" {
  name = "${local.engine_name}-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action    = "sts:AssumeRole"
      Effect    = "Allow"
      Principal = { Service = "ec2.amazonaws.com" }
    }]
  })

  tags = local.common_tags
}

resource "aws_iam_role_policy_attachment" "engine_eks_describe" {
  role       = aws_iam_role.engine.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKSClusterPolicy"
}

resource "aws_iam_instance_profile" "engine" {
  name = "${local.engine_name}-profile"
  role = aws_iam_role.engine.name
}

resource "aws_iam_role_policy" "engine_eks_api_access" {
  name = "${local.engine_name}-eks-api-access"
  role = aws_iam_role.engine.name

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "eks:AccessKubernetesApi",
          "eks:MutateViaKubernetesApi",
          "eks:DescribeCluster",
          "eks:ListClusters"
        ]
        Resource = aws_eks_cluster.main.arn
      }
    ]
  })
}

# ── EKS Access: grant engine role cluster-admin via access entries ─────────────
# (requires EKS access-mode = API or API_AND_CONFIG_MAP; default for new clusters)

resource "aws_eks_access_entry" "engine" {
  cluster_name  = aws_eks_cluster.main.name
  principal_arn = aws_iam_role.engine.arn
  type          = "STANDARD"

  depends_on = [aws_eks_cluster.main]
}

resource "aws_eks_access_policy_association" "engine_admin" {
  cluster_name  = aws_eks_cluster.main.name
  principal_arn = aws_iam_role.engine.arn
  policy_arn    = "arn:aws:eks::aws:cluster-access-policy/AmazonEKSClusterAdminPolicy"

  access_scope {
    type = "cluster"
  }

  depends_on = [aws_eks_access_entry.engine]
}

# ── Security Group: EKS control-plane ────────────────────────────────────────

resource "aws_security_group" "eks_cluster" {
  name        = "${local.eks_name}-sg"
  description = "EKS cluster control-plane security group"
  vpc_id      = aws_vpc.main.id

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(local.common_tags, { Name = "${local.eks_name}-sg" })
}

# ── EKS Cluster ───────────────────────────────────────────────────────────────

resource "aws_eks_cluster" "main" {
  name     = local.eks_name
  role_arn = aws_iam_role.eks_cluster.arn
  version  = var.eks_cluster_version

  vpc_config {
    subnet_ids              = [aws_subnet.public_a.id, aws_subnet.public_b.id]
    security_group_ids      = [aws_security_group.eks_cluster.id]
    endpoint_public_access  = true
    endpoint_private_access = true
  }

  depends_on = [
    aws_iam_role_policy_attachment.eks_cluster_policy,
  ]

  tags = local.common_tags
}

# ── EKS Managed Node Group ────────────────────────────────────────────────────

resource "aws_eks_node_group" "workers" {
  cluster_name    = aws_eks_cluster.main.name
  node_group_name = "${local.eks_name}-workers"
  node_role_arn   = aws_iam_role.eks_node.arn
  subnet_ids      = [aws_subnet.public_a.id, aws_subnet.public_b.id]
  instance_types  = [var.node_instance_type]
  disk_size       = var.node_disk_size_gb

  scaling_config {
    desired_size = var.node_count
    min_size     = var.node_min_count
    max_size     = var.node_max_count
  }

  depends_on = [
    aws_iam_role_policy_attachment.eks_node_policy,
    aws_iam_role_policy_attachment.eks_cni_policy,
    aws_iam_role_policy_attachment.eks_ecr_readonly,
  ]

  tags = local.common_tags
}

# ── Security Group: AkoFlow Engine ────────────────────────────────────────────

resource "aws_security_group" "engine" {
  name        = "${local.engine_name}-sg"
  description = "AkoFlow engine API access"
  vpc_id      = aws_vpc.main.id

  ingress {
    from_port   = var.akoflow_api_port
    to_port     = var.akoflow_api_port
    protocol    = "tcp"
    cidr_blocks = [var.akoflow_allowed_ips]
    description = "AkoFlow API"
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(local.common_tags, { Name = "${local.engine_name}-sg" })
}

# ── Data: latest Amazon Linux 2023 AMI ───────────────────────────────────────

data "aws_ami" "al2023" {
  most_recent = true
  owners      = ["amazon"]

  filter {
    name   = "name"
    values = ["al2023-ami-*-x86_64"]
  }
}

# ── AkoFlow Engine EC2 Instance ───────────────────────────────────────────────
# Order of operations: cluster first → access entry → node group → engine VM
# The startup script installs Docker, bootstraps AkoFlow, installs kubectl,
# authenticates to EKS, and creates a long-lived service-account token.

resource "aws_instance" "engine" {
  ami                         = data.aws_ami.al2023.id
  instance_type               = var.engine_instance_type
  subnet_id                   = aws_subnet.public_a.id
  vpc_security_group_ids      = [aws_security_group.engine.id]
  iam_instance_profile        = aws_iam_instance_profile.engine.name
  associate_public_ip_address = true

  user_data = <<-EOF
    #!/bin/bash
    set -eux

    # ── Install Docker ────────────────────────────────────────────────────────
    yum update -y
    yum install -y docker
    systemctl enable docker
    systemctl start docker

    # ── Bootstrap AkoFlow Engine ──────────────────────────────────────────────
    curl -fsSL https://akoflow.com/run | bash

    # ── Install kubectl ───────────────────────────────────────────────────────
    KUBECTL_VERSION=$(curl -L -s https://dl.k8s.io/release/stable.txt)
    curl -LO "https://dl.k8s.io/release/$${KUBECTL_VERSION}/bin/linux/amd64/kubectl"
    chmod +x kubectl
    mv kubectl /usr/local/bin/kubectl

    # ── Connect to EKS cluster via AWS CLI ────────────────────────────────────
    # The instance role has EKS access-policy AmazonEKSClusterAdminPolicy
    aws eks update-kubeconfig \
      --region ${var.region} \
      --name ${aws_eks_cluster.main.name}

    # ── Create service account and generate token ─────────────────────────────
    mkdir -p /etc/akoflow
    kubectl create serviceaccount akoflow-sa --namespace default 2>/dev/null || true
    kubectl create clusterrolebinding akoflow-sa-binding \
      --clusterrole=cluster-admin \
      --serviceaccount=default:akoflow-sa 2>/dev/null || true

    # Generate a long-lived token (8760 h ≈ 1 year)
    kubectl create token akoflow-sa --namespace default --duration=8760h \
      > /etc/akoflow/k8s-token

    # ── Write ~/akospace/.env ─────────────────────────────────────────────────
    K8S_TOKEN=$(cat /etc/akoflow/k8s-token)
    mkdir -p /root/akospace
    {
      echo "AKOFLOW_ENV=${var.akoflow_env}"
      echo "AKOFLOW_PORT=${var.akoflow_api_port}"
      echo ""
      echo "K8S_API_SERVER_HOST=${aws_eks_cluster.main.endpoint}"
      echo "K8S_API_SERVER_TOKEN=$${K8S_TOKEN}"
      echo ""
      echo "AKOFLOW_SERVER_SERVICE_SERVICE_HOST=host.docker.internal"
      echo "AKOFLOW_SERVER_SERVICE_SERVICE_PORT=${var.akoflow_api_port}"
    } > /root/akospace/.env

    echo "AkoFlow Engine ready. Cluster: ${aws_eks_cluster.main.endpoint}"
  EOF

  root_block_device {
    volume_size = var.engine_disk_size_gb
    volume_type = "gp3"
    encrypted   = true
  }

  tags = merge(local.common_tags, { Name = local.engine_name, Role = "akoflow-engine" })

  depends_on = [
    aws_eks_cluster.main,
    aws_eks_node_group.workers,
    aws_eks_access_policy_association.engine_admin,
  ]
}
