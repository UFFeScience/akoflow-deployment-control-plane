# ── Meta ──────────────────────────────────────────────────────────────────────

variable "environment_id" {
  description = "AkoCloud environment ID — injected automatically to guarantee unique resource names"
  type        = string
  default     = ""
}

# ── Cloud / Region ────────────────────────────────────────────────────────────

variable "region" {
  description = "AWS region to deploy into"
  type        = string
  default     = "us-east-1"
}

# ── Network ───────────────────────────────────────────────────────────────────

variable "vpc_cidr" {
  description = "CIDR block for the new VPC"
  type        = string
  default     = "10.0.0.0/16"
}

variable "subnet_public_1_cidr" {
  description = "CIDR block for the first public subnet (availability zone 1)"
  type        = string
  default     = "10.0.1.0/24"
}

variable "subnet_public_2_cidr" {
  description = "CIDR block for the second public subnet (availability zone 2)"
  type        = string
  default     = "10.0.2.0/24"
}

# ── Ubuntu Docker VM ──────────────────────────────────────────────────────────

variable "instance_type" {
  description = "EC2 instance type for the Ubuntu Docker VM"
  type        = string
  default     = "t3.micro"
}

variable "key_name" {
  description = "Name of an existing EC2 Key Pair to enable SSH access. Leave empty to disable SSH (port 22 will not be opened)."
  type        = string
  default     = ""
}

# ── EKS Cluster ───────────────────────────────────────────────────────────────

variable "cluster_name" {
  description = "Name suffix for the EKS cluster — final name is prefixed with the environment ID"
  type        = string
  default     = "eks"
}

variable "kubernetes_version" {
  description = "Kubernetes version for the EKS cluster"
  type        = string
  default     = "1.31"
}

# ── EKS Node Group ────────────────────────────────────────────────────────────

variable "node_instance_type" {
  description = "EC2 instance type for EKS worker nodes (minimum t3.small — EKS is not Free Tier eligible)"
  type        = string
  default     = "t3.small"
}

variable "desired_node_count" {
  description = "Desired number of nodes in the EKS node group"
  type        = number
  default     = 2
}

variable "min_node_count" {
  description = "Minimum number of nodes in the EKS node group"
  type        = number
  default     = 1
}

variable "max_node_count" {
  description = "Maximum number of nodes in the EKS node group"
  type        = number
  default     = 3
}
