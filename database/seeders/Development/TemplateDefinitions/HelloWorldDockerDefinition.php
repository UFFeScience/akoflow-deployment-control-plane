<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class HelloWorldDockerDefinition
{
    public static function get(): array
    {
        return [
            'environment_configuration' => [
                'label'       => 'Deployment Settings',
                'description' => 'Parâmetros para provisionar uma VM com Docker na AWS ou GCP.',
                'type'        => 'environment',
                'sections'    => [
                    // ── Cloud Provider ────────────────────────────────────────
                    [
                        'name'        => 'cloud',
                        'label'       => 'Cloud Provider',
                        'description' => 'Provedor, região e projeto.',
                        'fields'      => [
                            [
                                'name'     => 'cloud_provider',
                                'label'    => 'Provider',
                                'type'     => 'select',
                                'required' => true,
                                'default'  => 'aws',
                                'options'  => [
                                    ['label' => 'AWS', 'value' => 'aws'],
                                    ['label' => 'GCP', 'value' => 'gcp'],
                                ],
                            ],
                            [
                                'name'     => 'region',
                                'label'    => 'Region',
                                'type'     => 'string',
                                'required' => true,
                                'default'  => 'us-east-1',
                            ],
                            [
                                'name'     => 'zone',
                                'label'    => 'Zone / AZ (optional)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => '',
                            ],
                            [
                                'name'     => 'project_id',
                                'label'    => 'Project ID (GCP)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => '',
                            ],
                        ],
                    ],

                    // ── Instance ──────────────────────────────────────────────
                    [
                        'name'        => 'instance',
                        'label'       => 'Instance',
                        'description' => 'Tipo de máquina, imagem e nome.',
                        'fields'      => [
                            [
                                'name'     => 'instance_name',
                                'label'    => 'Instance Name',
                                'type'     => 'string',
                                'required' => true,
                                'default'  => 'hello-docker',
                            ],
                            [
                                'name'     => 'instance_type',
                                'label'    => 'Instance Type (AWS)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => 't3.micro',
                            ],
                            [
                                'name'     => 'machine_type',
                                'label'    => 'Machine Type (GCP)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => 'e2-micro',
                            ],
                            [
                                'name'     => 'associate_public_ip',
                                'label'    => 'Associate Public IP (AWS)',
                                'type'     => 'boolean',
                                'required' => false,
                                'default'  => true,
                            ],
                            // AWS image settings
                            [
                                'name'     => 'ami_id',
                                'label'    => 'AMI ID (AWS override)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => '',
                            ],
                            [
                                'name'     => 'ami_filter',
                                'label'    => 'AMI Name Filter (AWS)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => 'amzn2-ami-hvm-*-x86_64-gp2',
                            ],
                            [
                                'name'     => 'ami_owners',
                                'label'    => 'AMI Owner (AWS)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => 'amazon',
                            ],
                            // GCP image settings
                            [
                                'name'     => 'image_gcp',
                                'label'    => 'Boot Image Self-Link (GCP override)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => '',
                            ],
                            [
                                'name'     => 'image_family_gcp',
                                'label'    => 'Image Family (GCP)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => 'ubuntu-2204-lts',
                            ],
                            [
                                'name'     => 'image_project_gcp',
                                'label'    => 'Image Project (GCP)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => 'ubuntu-os-cloud',
                            ],
                        ],
                    ],

                    // ── Network / Security ────────────────────────────────────
                    [
                        'name'        => 'network',
                        'label'       => 'Network & Security',
                        'description' => 'VPC, sub-rede e regras de firewall.',
                        'fields'      => [
                            [
                                'name'     => 'vpc_id',
                                'label'    => 'VPC ID (AWS, optional)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => '',
                            ],
                            [
                                'name'     => 'subnet_id',
                                'label'    => 'Subnet ID (AWS, optional)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => '',
                            ],
                            [
                                'name'     => 'network_gcp',
                                'label'    => 'Network (GCP)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => 'default',
                            ],
                            [
                                'name'     => 'ingress_from_port',
                                'label'    => 'Ingress From Port',
                                'type'     => 'number',
                                'required' => false,
                                'default'  => 80,
                            ],
                            [
                                'name'     => 'ingress_to_port',
                                'label'    => 'Ingress To Port',
                                'type'     => 'number',
                                'required' => false,
                                'default'  => 80,
                            ],
                            [
                                'name'     => 'ingress_protocol',
                                'label'    => 'Ingress Protocol',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => 'tcp',
                            ],
                            [
                                'name'     => 'ingress_cidr',
                                'label'    => 'Ingress CIDR',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => '0.0.0.0/0',
                            ],
                            [
                                'name'     => 'egress_cidr',
                                'label'    => 'Egress CIDR (AWS)',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => '0.0.0.0/0',
                            ],
                        ],
                    ],

                    // ── Application ───────────────────────────────────────────
                    [
                        'name'        => 'application',
                        'label'       => 'Application',
                        'description' => 'Script de inicialização executado no boot da VM.',
                        'fields'      => [
                            [
                                'name'     => 'user_data',
                                'label'    => 'User Data / Startup Script',
                                'type'     => 'script',
                                'required' => true,
                                'default'  => implode("\n", [
                                    '#!/bin/bash',
                                    'set -eux',
                                    '',
                                    '# Install Docker',
                                    'if command -v apt-get &>/dev/null; then',
                                    '  apt-get update -y',
                                    '  apt-get install -y docker.io',
                                    'else',
                                    '  yum update -y',
                                    '  amazon-linux-extras install docker -y || yum install -y docker || true',
                                    'fi',
                                    '',
                                    'systemctl enable docker',
                                    'systemctl start docker',
                                    '',
                                    '# Run container',
                                    'docker run -d --name app --restart always -p 80:80 nginx:latest',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],

            'instance_configurations' => [
                'single-vm' => [
                    'label'    => 'Single VM',
                    'type'     => 'vm',
                    'sections' => [],
                ],
            ],
        ];
    }
}
