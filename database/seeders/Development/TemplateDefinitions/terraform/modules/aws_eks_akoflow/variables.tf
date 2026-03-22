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

# ── EKS Cluster ───────────────────────────────────────────────────────────────

variable "eks_cluster_version" {
  description = "Kubernetes version for the EKS cluster"
  type        = string
  default     = "1.32"
}

variable "node_instance_type" {
  description = "EC2 instance type for EKS managed node group workers"
  type        = string
  default     = "t3.medium"
}

variable "node_disk_size_gb" {
  description = "Disk size in GB for each EKS worker node"
  type        = number
  default     = 50
}

variable "node_count" {
  description = "Desired number of EKS worker nodes"
  type        = number
  default     = 1
}

variable "node_min_count" {
  description = "Minimum number of EKS worker nodes (autoscaling)"
  type        = number
  default     = 1
}

variable "node_max_count" {
  description = "Maximum number of EKS worker nodes (autoscaling)"
  type        = number
  default     = 5
}

# ── AkoFlow Engine ────────────────────────────────────────────────────────────

variable "engine_instance_type" {
  description = "EC2 instance type for the AkoFlow engine VM"
  type        = string
  default     = "t3.medium"
}

variable "engine_disk_size_gb" {
  description = "Root volume size in GB for the AkoFlow engine VM"
  type        = number
  default     = 50
}

variable "akoflow_api_port" {
  description = "TCP port the AkoFlow API listens on (opened in the security group)"
  type        = number
  default     = 8080
}

variable "akoflow_allowed_ips" {
  description = "CIDR block allowed to reach the AkoFlow API port"
  type        = string
  default     = "0.0.0.0/0"
}

variable "akoflow_env" {
  description = "AkoFlow environment name written to ~/akospace/.env as AKOFLOW_ENV"
  type        = string
  default     = "dev"
}
