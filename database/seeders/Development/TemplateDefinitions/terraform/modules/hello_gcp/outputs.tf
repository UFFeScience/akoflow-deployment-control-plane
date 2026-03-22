output "public_ip" {
  description = "Public (NAT) IP of the instance"
  value       = google_compute_instance.nginx.network_interface[0].access_config[0].nat_ip
}

output "private_ip" {
  description = "Internal IP of the instance"
  value       = google_compute_instance.nginx.network_interface[0].network_ip
}

output "instance_name" {
  description = "Full resource name of the compute instance"
  value       = google_compute_instance.nginx.name
}

output "instance_id" {
  description = "Unique numeric ID of the compute instance"
  value       = google_compute_instance.nginx.instance_id
}

output "firewall_name" {
  description = "Name of the firewall rule created for the instance"
  value       = google_compute_firewall.nginx.name
}

output "resolved_image" {
  description = "Boot image used by the instance"
  value       = google_compute_instance.nginx.boot_disk[0].initialize_params[0].image
}

output "nginx_url" {
  description = "NGINX URL (public IP + configured port)"
  value       = "http://${google_compute_instance.nginx.network_interface[0].access_config[0].nat_ip}:${var.nginx_port}"
}

output "akoflow_iframe_url" {
  description = "URL exposed as iframe inside AkoCloud (same as nginx_url)"
  value       = "http://${google_compute_instance.nginx.network_interface[0].access_config[0].nat_ip}:${var.nginx_port}"
}
