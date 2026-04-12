terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = ">= 5.0"
    }
    google = {
      source  = "hashicorp/google"
      version = ">= 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region
}

provider "google" {
  project = var.gcp_project_id
  region  = var.gcp_region
}

locals {
  resource_prefix  = var.environment_id != "" ? "akocloud-${var.environment_id}" : "akocloud"
  eks_cluster_name = "${local.resource_prefix}-eks"
  gke_cluster_name = "${local.resource_prefix}-gke"
}

# ── AWS: Availability Zones ───────────────────────────────────────────────────

data "aws_availability_zones" "available" {
  state = "available"
}

# ── AWS: VPC ──────────────────────────────────────────────────────────────────

resource "aws_vpc" "main" {
  cidr_block           = var.aws_vpc_cidr
  enable_dns_hostnames = true
  enable_dns_support   = true

  tags = {
    Name        = "${local.resource_prefix}-vpc"
    Environment = var.environment_id
  }
}

resource "aws_internet_gateway" "main" {
  vpc_id = aws_vpc.main.id

  tags = {
    Name        = "${local.resource_prefix}-igw"
    Environment = var.environment_id
  }
}

# ── AWS: Public Subnets (2 AZs required by EKS) ──────────────────────────────

resource "aws_subnet" "public_1" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = var.aws_subnet_1_cidr
  availability_zone       = data.aws_availability_zones.available.names[0]
  map_public_ip_on_launch = true

  tags = {
    Name                                            = "${local.resource_prefix}-public-1"
    "kubernetes.io/cluster/${local.eks_cluster_name}" = "shared"
    "kubernetes.io/role/elb"                          = "1"
    Environment                                       = var.environment_id
  }
}

resource "aws_subnet" "public_2" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = var.aws_subnet_2_cidr
  availability_zone       = data.aws_availability_zones.available.names[1]
  map_public_ip_on_launch = true

  tags = {
    Name                                            = "${local.resource_prefix}-public-2"
    "kubernetes.io/cluster/${local.eks_cluster_name}" = "shared"
    "kubernetes.io/role/elb"                          = "1"
    Environment                                       = var.environment_id
  }
}

resource "aws_route_table" "public" {
  vpc_id = aws_vpc.main.id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.main.id
  }

  tags = {
    Name        = "${local.resource_prefix}-rt-public"
    Environment = var.environment_id
  }
}

resource "aws_route_table_association" "public_1" {
  subnet_id      = aws_subnet.public_1.id
  route_table_id = aws_route_table.public.id
}

resource "aws_route_table_association" "public_2" {
  subnet_id      = aws_subnet.public_2.id
  route_table_id = aws_route_table.public.id
}

# ── AWS: IAM Role — EKS Cluster ───────────────────────────────────────────────

resource "aws_iam_role" "eks_cluster" {
  name = "${local.resource_prefix}-eks-cluster-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action    = ["sts:AssumeRole", "sts:TagSession"]
      Effect    = "Allow"
      Principal = { Service = "eks.amazonaws.com" }
    }]
  })
}

resource "aws_iam_role_policy_attachment" "eks_cluster_policy" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKSClusterPolicy"
  role       = aws_iam_role.eks_cluster.name
}

# ── AWS: IAM Role — EKS Nodes ─────────────────────────────────────────────────

resource "aws_iam_role" "eks_nodes" {
  name = "${local.resource_prefix}-eks-nodes-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action    = "sts:AssumeRole"
      Effect    = "Allow"
      Principal = { Service = "ec2.amazonaws.com" }
    }]
  })
}

resource "aws_iam_role_policy_attachment" "eks_worker_node" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKSWorkerNodePolicy"
  role       = aws_iam_role.eks_nodes.name
}

resource "aws_iam_role_policy_attachment" "eks_cni" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEKS_CNI_Policy"
  role       = aws_iam_role.eks_nodes.name
}

resource "aws_iam_role_policy_attachment" "eks_ecr" {
  policy_arn = "arn:aws:iam::aws:policy/AmazonEC2ContainerRegistryReadOnly"
  role       = aws_iam_role.eks_nodes.name
}

# ── AWS: IAM Role — EC2 AkoFlow Server ───────────────────────────────────────

resource "aws_iam_role" "ec2_instance" {
  name = "${local.resource_prefix}-ec2-akoflow-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Action    = "sts:AssumeRole"
      Effect    = "Allow"
      Principal = { Service = "ec2.amazonaws.com" }
    }]
  })
}

resource "aws_iam_role_policy" "ec2_eks_describe" {
  name = "${local.resource_prefix}-ec2-eks-describe"
  role = aws_iam_role.ec2_instance.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect   = "Allow"
      Action   = ["eks:DescribeCluster", "eks:ListClusters"]
      Resource = "*"
    }]
  })
}

resource "aws_iam_instance_profile" "ec2_instance" {
  name = "${local.resource_prefix}-ec2-akoflow-profile"
  role = aws_iam_role.ec2_instance.name
}

# ── AWS: EKS Cluster ──────────────────────────────────────────────────────────

resource "aws_eks_cluster" "main" {
  name     = local.eks_cluster_name
  version  = var.eks_kubernetes_version
  role_arn = aws_iam_role.eks_cluster.arn

  access_config {
    authentication_mode = "API_AND_CONFIG_MAP"
  }

  vpc_config {
    endpoint_public_access = true
    subnet_ids             = [aws_subnet.public_1.id, aws_subnet.public_2.id]
  }

  depends_on = [aws_iam_role_policy_attachment.eks_cluster_policy]

  tags = {
    Name        = local.eks_cluster_name
    Environment = var.environment_id
  }
}

# Give the EC2 instance's IAM role cluster-admin access to EKS
resource "aws_eks_access_entry" "ec2_instance" {
  cluster_name  = aws_eks_cluster.main.name
  principal_arn = aws_iam_role.ec2_instance.arn
  type          = "STANDARD"

  depends_on = [aws_eks_cluster.main]
}

resource "aws_eks_access_policy_association" "ec2_admin" {
  cluster_name  = aws_eks_cluster.main.name
  principal_arn = aws_iam_role.ec2_instance.arn
  policy_arn    = "arn:aws:eks::aws:cluster-access-policy/AmazonEKSClusterAdminPolicy"

  access_scope { type = "cluster" }

  depends_on = [aws_eks_access_entry.ec2_instance]
}

# ── AWS: EKS Node Group ───────────────────────────────────────────────────────

resource "aws_eks_node_group" "main" {
  cluster_name    = aws_eks_cluster.main.name
  node_group_name = "${local.resource_prefix}-ng"
  node_role_arn   = aws_iam_role.eks_nodes.arn
  subnet_ids      = [aws_subnet.public_1.id, aws_subnet.public_2.id]
  instance_types  = [var.eks_node_instance_type]

  scaling_config {
    desired_size = var.eks_desired_nodes
    max_size     = var.eks_max_nodes
    min_size     = var.eks_min_nodes
  }

  update_config { max_unavailable = 1 }

  depends_on = [
    aws_iam_role_policy_attachment.eks_worker_node,
    aws_iam_role_policy_attachment.eks_cni,
    aws_iam_role_policy_attachment.eks_ecr,
  ]

  tags = {
    Name        = "${local.resource_prefix}-ng"
    Environment = var.environment_id
  }
}

# ── AWS: Ubuntu AMI ───────────────────────────────────────────────────────────

data "aws_ami" "ubuntu" {
  most_recent = true

  filter {
    name   = "name"
    values = ["ubuntu/images/hvm-ssd/ubuntu-jammy-22.04-amd64-server-*"]
  }

  filter {
    name   = "virtualization-type"
    values = ["hvm"]
  }

  owners = ["099720109477"] # Canonical
}

# ── AWS: Security Group — AkoFlow Server ─────────────────────────────────────

resource "aws_security_group" "akoflow" {
  name_prefix = "${local.resource_prefix}-akoflow-"
  description = "AkoFlow server ports 80 and 8080"
  vpc_id      = aws_vpc.main.id

  ingress {
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
    description = "HTTP"
  }

  ingress {
    from_port   = 8080
    to_port     = 8080
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
    description = "AkoFlow API"
  }

  dynamic "ingress" {
    for_each = var.key_name != "" ? [1] : []
    content {
      from_port   = 22
      to_port     = 22
      protocol    = "tcp"
      cidr_blocks = ["0.0.0.0/0"]
      description = "SSH"
    }
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name        = "${local.resource_prefix}-akoflow-sg"
    Environment = var.environment_id
  }

  lifecycle { create_before_destroy = true }
}

# ── AWS: EC2 AkoFlow Server ───────────────────────────────────────────────────

resource "aws_instance" "akoflow" {
  ami                         = data.aws_ami.ubuntu.id
  instance_type               = var.ec2_instance_type
  iam_instance_profile        = aws_iam_instance_profile.ec2_instance.name
  vpc_security_group_ids      = [aws_security_group.akoflow.id]
  associate_public_ip_address = true
  availability_zone           = data.aws_availability_zones.available.names[0]
  subnet_id                   = aws_subnet.public_1.id
  key_name                    = var.key_name != "" ? var.key_name : null

  tags = {
    Name        = "${local.resource_prefix}-akoflow"
    Environment = var.environment_id
  }

  # Wait for both clusters and the EKS access entry to be fully ready
  depends_on = [
    aws_eks_node_group.main,
    aws_eks_access_policy_association.ec2_admin,
    google_container_node_pool.main,
  ]
}

# ── GCP: VPC Network ──────────────────────────────────────────────────────────

resource "google_compute_network" "main" {
  name                    = "${local.resource_prefix}-network"
  auto_create_subnetworks = false
}

resource "google_compute_subnetwork" "main" {
  name                     = "${local.resource_prefix}-subnet"
  ip_cidr_range            = var.gcp_subnet_cidr
  region                   = var.gcp_region
  network                  = google_compute_network.main.id
  private_ip_google_access = true

  secondary_ip_range {
    range_name    = "pods"
    ip_cidr_range = var.gcp_pods_cidr
  }

  secondary_ip_range {
    range_name    = "services"
    ip_cidr_range = var.gcp_services_cidr
  }
}

# ── GCP: Service Account — GKE Nodes ─────────────────────────────────────────

resource "google_service_account" "gke_nodes" {
  account_id   = substr("${local.resource_prefix}-gke-sa", 0, 30)
  display_name = "GKE nodes — ${local.resource_prefix}"
}

resource "google_project_iam_member" "gke_log_writer" {
  project = var.gcp_project_id
  role    = "roles/logging.logWriter"
  member  = "serviceAccount:${google_service_account.gke_nodes.email}"
}

resource "google_project_iam_member" "gke_metric_writer" {
  project = var.gcp_project_id
  role    = "roles/monitoring.metricWriter"
  member  = "serviceAccount:${google_service_account.gke_nodes.email}"
}

resource "google_project_iam_member" "gke_artifact_reader" {
  project = var.gcp_project_id
  role    = "roles/artifactregistry.reader"
  member  = "serviceAccount:${google_service_account.gke_nodes.email}"
}

# ── GCP: GKE Cluster ──────────────────────────────────────────────────────────

resource "google_container_cluster" "main" {
  name     = local.gke_cluster_name
  location = var.gcp_region

  remove_default_node_pool = true
  initial_node_count       = 1

  network    = google_compute_network.main.id
  subnetwork = google_compute_subnetwork.main.id

  ip_allocation_policy {
    cluster_secondary_range_name  = "pods"
    services_secondary_range_name = "services"
  }

  min_master_version  = var.gke_kubernetes_version
  deletion_protection = false
}

# ── GCP: GKE Node Pool ────────────────────────────────────────────────────────

resource "google_container_node_pool" "main" {
  name       = "${local.resource_prefix}-pool"
  location   = var.gcp_region
  cluster    = google_container_cluster.main.name

  initial_node_count = var.gke_desired_nodes

  autoscaling {
    min_node_count = var.gke_min_nodes
    max_node_count = var.gke_max_nodes
  }

  node_config {
    machine_type    = var.gke_node_machine_type
    service_account = google_service_account.gke_nodes.email
    image_type      = "COS_CONTAINERD"

    oauth_scopes = ["https://www.googleapis.com/auth/cloud-platform"]

    labels = { environment = var.environment_id }
  }

  management {
    auto_repair  = true
    auto_upgrade = true
  }
}
