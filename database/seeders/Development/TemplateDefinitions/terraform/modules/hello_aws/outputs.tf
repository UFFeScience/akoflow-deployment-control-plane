output "instance_id" {
  description = "EC2 instance ID"
  value       = aws_instance.hello.id
}

output "public_ip" {
  description = "Public IP address of the instance"
  value       = aws_instance.hello.public_ip
}

output "private_ip" {
  description = "Private IP address of the instance"
  value       = aws_instance.hello.private_ip
}

output "security_group_id" {
  description = "ID of the security group attached to the instance"
  value       = aws_security_group.hello.id
}

output "resolved_ami" {
  description = "AMI ID that was actually used"
  value       = local.resolved_ami
}
