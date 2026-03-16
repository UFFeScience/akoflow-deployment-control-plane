output "public_ip" {
  description = "Public IP of the hello instance"
  value       = google_compute_instance.hello.network_interface[0].access_config[0].nat_ip
}

output "instance_name" {
  description = "Name of the hello instance"
  value       = google_compute_instance.hello.name
}
