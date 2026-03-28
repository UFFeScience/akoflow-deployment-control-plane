# ── Ubuntu Docker VM ──────────────────────────────────────────────────────────

output "docker_vm_instance_name" {
  description = "Compute Engine instance name for the Ubuntu Docker VM"
  value       = google_compute_instance.docker_vm.name
}

output "public_ip" {
  description = "Public IP address of the Ubuntu Docker VM"
  value       = google_compute_instance.docker_vm.network_interface[0].access_config[0].nat_ip
}

output "private_ip" {
  description = "Private IP address of the Ubuntu Docker VM"
  value       = google_compute_instance.docker_vm.network_interface[0].network_ip
}

output "docker_vm_self_link" {
  description = "Self-link of the Ubuntu Docker VM instance"
  value       = google_compute_instance.docker_vm.self_link
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

output "gke_cluster_ca_certificate" {
  description = "Base64-encoded certificate authority certificate for the GKE cluster"
  value       = google_container_cluster.main.master_auth[0].cluster_ca_certificate
  sensitive   = true
}

output "gke_kubernetes_version" {
  description = "Kubernetes version running on the cluster"
  value       = google_container_cluster.main.min_master_version
}

output "gke_node_pool_name" {
  description = "Managed node pool name"
  value       = google_container_node_pool.main.name
}

# ── Network ───────────────────────────────────────────────────────────────────

output "network_name" {
  description = "VPC network name"
  value       = google_compute_network.main.name
}

output "subnet_name" {
  description = "Subnetwork name"
  value       = google_compute_subnetwork.main.name
}

# ── AkoCloud ──────────────────────────────────────────────────────────────────

output "akoflow_iframe_url" {
  description = "URL exposed as iframe inside AkoCloud (Docker VM port 8080)"
  value       = "http://${google_compute_instance.docker_vm.network_interface[0].access_config[0].nat_ip}:8080"
}
