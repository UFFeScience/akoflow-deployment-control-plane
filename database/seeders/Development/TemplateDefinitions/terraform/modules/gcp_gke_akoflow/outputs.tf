output "gke_cluster_name" {
  description = "GKE cluster name"
  value       = google_container_cluster.main.name
}

output "gke_cluster_endpoint" {
  description = "GKE control-plane endpoint URL"
  value       = google_container_cluster.main.endpoint
  sensitive   = true
}

output "gke_cluster_version" {
  description = "GKE master Kubernetes version"
  value       = google_container_cluster.main.master_version
}

output "engine_instance_name" {
  description = "Compute instance name of the AkoFlow engine VM"
  value       = google_compute_instance.engine.name
}

output "engine_public_ip" {
  description = "External (public) IP of the AkoFlow engine VM"
  value       = google_compute_instance.engine.network_interface[0].access_config[0].nat_ip
}

output "engine_private_ip" {
  description = "Internal IP of the AkoFlow engine VM"
  value       = google_compute_instance.engine.network_interface[0].network_ip
}

output "akoflow_api_url" {
  description = "AkoFlow API base URL"
  value       = "http://${google_compute_instance.engine.network_interface[0].access_config[0].nat_ip}:${var.akoflow_api_port}"
}

output "akoflow_iframe_url" {
  description = "AkoFlow URL exposed as iframe inside AkoCloud"
  value       = "http://${google_compute_instance.engine.network_interface[0].access_config[0].nat_ip}:${var.akoflow_api_port}"
}

output "vpc_name" {
  description = "VPC network name"
  value       = google_compute_network.vpc.name
}
