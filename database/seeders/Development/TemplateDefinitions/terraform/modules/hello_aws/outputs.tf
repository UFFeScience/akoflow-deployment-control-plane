output "instance_id" {
  description = "EC2 instance ID"
  value       = aws_instance.nginx.id
}

output "public_ip" {
  description = "Public IP address of the instance"
  value       = aws_instance.nginx.public_ip
}

output "private_ip" {
  description = "Private IP address of the instance"
  value       = aws_instance.nginx.private_ip
}

output "security_group_id" {
  description = "ID of the security group attached to the instance"
  value       = aws_security_group.nginx.id
}

output "resolved_ami" {
  description = "AMI ID that was actually used"
  value       = local.resolved_ami
}

output "nginx_url" {
  description = "NGINX URL (public IP + configured port)"
  value       = "http://${aws_instance.nginx.public_ip}:${var.nginx_port}"
}

output "akoflow_iframe_url" {
  description = "URL exposed as iframe inside AkoCloud (same as nginx_url)"
  value       = "http://${aws_instance.nginx.public_ip}:${var.nginx_port}"
}
