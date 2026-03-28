# ── Meta ──────────────────────────────────────────────────────────────────────

variable "environment_id" {
  description = "AkoCloud environment ID — injected automatically"
  type        = string
  default     = ""
}

# ── AWS ───────────────────────────────────────────────────────────────────────

variable "aws_region" {
  description = "AWS region for the EC2 instance and EKS cluster"
  type        = string
  default     = "us-east-1"
}

variable "aws_vpc_cidr" {
  description = "CIDR block for the AWS VPC"
  type        = string
  default     = "10.0.0.0/16"
}

variable "aws_subnet_1_cidr" {
  description = "CIDR for the first public subnet (AZ 1)"
  type        = string
  default     = "10.0.1.0/24"
}

variable "aws_subnet_2_cidr" {
  description = "CIDR for the second public subnet (AZ 2)"
  type        = string
  default     = "10.0.2.0/24"
}

variable "ec2_instance_type" {
  description = "EC2 instance type for the AkoFlow server VM"
  type        = string
  default     = "t3.small"
}

variable "key_name" {
  description = "Name of an existing EC2 Key Pair for SSH access. Leave empty to disable SSH."
  type        = string
  default     = ""
}

# ── EKS ───────────────────────────────────────────────────────────────────────

variable "eks_kubernetes_version" {
  description = "Kubernetes version for the EKS cluster"
  type        = string
  default     = "1.31"
}

variable "eks_node_instance_type" {
  description = "EC2 instance type for EKS worker nodes (minimum t3.small)"
  type        = string
  default     = "t3.small"
}

variable "eks_desired_nodes" {
  description = "Desired number of EKS worker nodes"
  type        = number
  default     = 2
}

variable "eks_min_nodes" {
  description = "Minimum number of EKS worker nodes"
  type        = number
  default     = 1
}

variable "eks_max_nodes" {
  description = "Maximum number of EKS worker nodes"
  type        = number
  default     = 3
}

# ── GCP ───────────────────────────────────────────────────────────────────────

variable "gcp_project_id" {
  description = "GCP project ID"
  type        = string
}

variable "gcp_region" {
  description = "GCP region for the GKE cluster"
  type        = string
  default     = "us-central1"
}

variable "gcp_sa_key_json" {
  description = "GCP service account JSON key. Used by the AkoFlow server to authenticate with GKE."
  type        = string
  sensitive   = true
}

variable "gcp_subnet_cidr" {
  description = "Primary CIDR for the GCP subnet"
  type        = string
  default     = "10.1.0.0/24"
}

variable "gcp_pods_cidr" {
  description = "Secondary CIDR range for GKE pods"
  type        = string
  default     = "10.48.0.0/14"
}

variable "gcp_services_cidr" {
  description = "Secondary CIDR range for GKE services"
  type        = string
  default     = "10.52.0.0/20"
}

# ── GKE ───────────────────────────────────────────────────────────────────────

variable "gke_kubernetes_version" {
  description = "Minimum Kubernetes master version for the GKE cluster"
  type        = string
  default     = "1.31"
}

variable "gke_node_machine_type" {
  description = "Compute Engine machine type for GKE worker nodes"
  type        = string
  default     = "e2-medium"
}

variable "gke_desired_nodes" {
  description = "Initial number of GKE nodes per zone"
  type        = number
  default     = 2
}

variable "gke_min_nodes" {
  description = "Minimum number of GKE nodes per zone"
  type        = number
  default     = 1
}

variable "gke_max_nodes" {
  description = "Maximum number of GKE nodes per zone"
  type        = number
  default     = 3
}
