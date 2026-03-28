# ── AkoFlow Server (EC2) ──────────────────────────────────────────────────────

output "akoflow_instance_id" {
  description = "EC2 instance ID of the AkoFlow server"
  value       = aws_instance.akoflow.id
}

output "public_ip" {
  description = "Public IP of the AkoFlow server"
  value       = aws_instance.akoflow.public_ip
}

output "private_ip" {
  description = "Private IP of the AkoFlow server"
  value       = aws_instance.akoflow.private_ip
}

output "akoflow_iframe_url" {
  description = "AkoFlow web UI URL"
  value       = "http://${aws_instance.akoflow.public_ip}:8080"
}

output "setup_log_hint" {
  description = "How to follow the setup log on the instance"
  value       = "ssh ubuntu@${aws_instance.akoflow.public_ip} sudo tail -f /var/log/akoflow-setup.log"
}

# ── EKS Cluster ───────────────────────────────────────────────────────────────

output "eks_cluster_name" {
  description = "EKS cluster name"
  value       = aws_eks_cluster.main.name
}

output "eks_cluster_endpoint" {
  description = "EKS API server endpoint"
  value       = aws_eks_cluster.main.endpoint
}

output "eks_cluster_arn" {
  description = "EKS cluster ARN"
  value       = aws_eks_cluster.main.arn
}

output "eks_kubernetes_version" {
  description = "Kubernetes version of the EKS cluster"
  value       = aws_eks_cluster.main.version
}

# ── GKE Cluster ───────────────────────────────────────────────────────────────

output "gke_cluster_name" {
  description = "GKE cluster name"
  value       = google_container_cluster.main.name
}

output "gke_cluster_endpoint" {
  description = "GKE API server endpoint"
  value       = google_container_cluster.main.endpoint
  sensitive   = true
}

output "gke_kubernetes_version" {
  description = "Kubernetes version of the GKE cluster"
  value       = google_container_cluster.main.min_master_version
}

output "gke_node_pool_name" {
  description = "GKE managed node pool name"
  value       = google_container_node_pool.main.name
}

# ── Network ───────────────────────────────────────────────────────────────────

output "aws_vpc_id" {
  description = "AWS VPC ID"
  value       = aws_vpc.main.id
}

output "gcp_network_name" {
  description = "GCP VPC network name"
  value       = google_compute_network.main.name
}
