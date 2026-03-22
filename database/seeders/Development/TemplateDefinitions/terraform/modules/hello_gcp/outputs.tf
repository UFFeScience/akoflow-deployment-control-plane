output "public_ip" {
  description = "Public (NAT) IP of the instance"
  value       = google_compute_instance.hello.network_interface[0].access_config[0].nat_ip
}

output "private_ip" {
  description = "Internal IP of the instance"
  value       = google_compute_instance.hello.network_interface[0].network_ip
}

output "instance_name" {
  description = "Full resource name of the compute instance"
  value       = google_compute_instance.hello.name
}

output "instance_id" {
  description = "Unique numeric ID of the compute instance"
  value       = google_compute_instance.hello.instance_id
}

output "firewall_name" {
  description = "Name of the firewall rule created for the instance"
  value       = google_compute_firewall.hello.name
}

output "resolved_image" {
  description = "Boot image used by the instance"
  value       = google_compute_instance.hello.boot_disk[0].initialize_params[0].image
}
