output "public_ip" {
  description = "Public IP of the hello instance"
  value       = aws_instance.hello.public_ip
}

output "instance_id" {
  description = "ID of the hello instance"
  value       = aws_instance.hello.id
}
