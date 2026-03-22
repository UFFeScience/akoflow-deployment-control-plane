output "gke_deployment_name" {
  description = "GKE deployment name"
  value       = google_container_deployment.primary.name
}

output "gke_endpoint" {
  description = "GKE deployment endpoint"
  value       = google_container_deployment.primary.endpoint
  sensitive   = true
}

output "akoflow_instance_name" {
  description = "Akoflow compute instance name"
  value       = google_compute_instance.akoflow.name
}

output "akoflow_public_ip" {
  description = "Akoflow public IP (if enabled)"
  value = (
    var.akoflow_enable_public_ip ?
    google_compute_instance.akoflow.network_interface[0].access_config[0].nat_ip :
    null
  )
}

output "vpc_name" {
  description = "VPC network name"
  value       = google_compute_network.vpc.name
}
