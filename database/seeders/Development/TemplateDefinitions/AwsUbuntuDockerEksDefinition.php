<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class AwsUbuntuDockerEksDefinition
{
    public static function get(): array
    {
        return [
            // Only AWS is supported by this template.
            'providers' => ['aws'],

            // AWS credentials are mandatory.
            'required_providers' => ['aws'],

            'environment_configuration' => [
                'label'       => 'Ubuntu + Docker + EKS Settings',
                'description' => 'Provisions an Ubuntu 22.04 EC2 instance with Docker CE and an Amazon EKS cluster inside a dedicated VPC.',
                'type'        => 'environment',

                'groups' => [
                    [
                        'name'        => 'network',
                        'label'       => 'Network',
                        'description' => 'VPC and subnet configuration shared by the Docker VM and the EKS cluster.',
                        'icon'        => 'network',
                    ],
                    [
                        'name'        => 'docker_vm',
                        'label'       => 'Ubuntu Docker VM',
                        'description' => 'Ubuntu 22.04 LTS instance with Docker CE pre-installed.',
                        'icon'        => 'server',
                    ],
                    [
                        'name'        => 'eks',
                        'label'       => 'EKS Cluster',
                        'description' => 'Amazon Elastic Kubernetes Service cluster and managed node group.',
                        'icon'        => 'settings',
                    ],
                ],

                'sections' => [

                    // ── Cloud Provider ────────────────────────────────────────
                    [
                        'name'        => 'cloud',
                        'label'       => 'Cloud Provider',
                        'description' => 'AWS region for all resources.',
                        'group'       => 'network',
                        'fields'      => [
                            [
                                'name'     => 'region',
                                'label'    => 'Region',
                                'type'     => 'string',
                                'required' => true,
                                'default'  => 'us-east-1',
                            ],
                        ],
                    ],

                    // ── VPC & Subnets ──────────────────────────────────────────
                    [
                        'name'        => 'vpc',
                        'label'       => 'VPC & Subnets',
                        'description' => 'A dedicated VPC with two public subnets spanning different availability zones (required by EKS).',
                        'group'       => 'network',
                        'fields'      => [
                            [
                                'name'        => 'vpc_cidr',
                                'label'       => 'VPC CIDR',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '10.0.0.0/16',
                                'description' => 'CIDR block for the new VPC.',
                            ],
                            [
                                'name'        => 'subnet_public_1_cidr',
                                'label'       => 'Public Subnet 1 CIDR',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '10.0.1.0/24',
                                'description' => 'CIDR for the first public subnet (availability zone 1).',
                            ],
                            [
                                'name'        => 'subnet_public_2_cidr',
                                'label'       => 'Public Subnet 2 CIDR',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '10.0.2.0/24',
                                'description' => 'CIDR for the second public subnet (availability zone 2).',
                            ],
                        ],
                    ],

                    // ── Ubuntu Docker VM ───────────────────────────────────────
                    [
                        'name'        => 'docker_instance',
                        'label'       => 'Ubuntu Docker Instance',
                        'description' => 'Launches an Ubuntu 22.04 LTS instance and automatically installs Docker CE via user-data.',
                        'group'       => 'docker_vm',
                        'fields'      => [
                            [
                                'name'        => 'instance_type',
                                'label'       => 'Instance Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 't3.micro',
                                'description' => 'EC2 instance type for the Ubuntu Docker VM. t3.micro is Free Tier eligible.',
                            ],
                            [
                                'name'        => 'key_name',
                                'label'       => 'Key Pair Name (optional)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '',
                                'description' => 'Name of an existing EC2 Key Pair to enable SSH access. Leave blank to skip.',
                            ],
                        ],
                    ],

                    // ── EKS Cluster ────────────────────────────────────────────
                    [
                        'name'        => 'eks_cluster',
                        'label'       => 'EKS Cluster',
                        'description' => 'Amazon EKS cluster configuration.',
                        'group'       => 'eks',
                        'fields'      => [
                            [
                                'name'        => 'cluster_name',
                                'label'       => 'Cluster Name',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'eks',
                                'description' => 'Name suffix for the EKS cluster. The final name is prefixed with the environment ID.',
                            ],
                            [
                                'name'        => 'kubernetes_version',
                                'label'       => 'Kubernetes Version',
                                'type'        => 'select',
                                'required'    => true,
                                'default'     => '1.31',
                                'options'     => [
                                    ['label' => '1.32', 'value' => '1.32'],
                                    ['label' => '1.31', 'value' => '1.31'],
                                    ['label' => '1.30', 'value' => '1.30'],
                                    ['label' => '1.29', 'value' => '1.29'],
                                ],
                                'description' => 'Kubernetes version for the EKS control plane.',
                            ],
                        ],
                    ],

                    // ── EKS Node Group ─────────────────────────────────────────
                    [
                        'name'        => 'eks_nodes',
                        'label'       => 'Node Group',
                        'description' => 'Managed node group that runs the Kubernetes workloads.',
                        'group'       => 'eks',
                        'fields'      => [
                            [
                                'name'        => 'node_instance_type',
                                'label'       => 'Node Instance Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 't3.small',
                                'description' => 'EC2 instance type for EKS worker nodes. Minimum recommended: t3.small (2 vCPU / 2 GB). EKS nodes are NOT Free Tier eligible.',
                            ],
                            [
                                'name'        => 'desired_node_count',
                                'label'       => 'Desired Node Count',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 2,
                                'min'         => 1,
                                'max'         => 10,
                                'description' => 'Desired number of worker nodes.',
                            ],
                            [
                                'name'        => 'min_node_count',
                                'label'       => 'Minimum Node Count',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 1,
                                'min'         => 1,
                                'max'         => 10,
                                'description' => 'Minimum number of nodes in the autoscaling group.',
                            ],
                            [
                                'name'        => 'max_node_count',
                                'label'       => 'Maximum Node Count',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 3,
                                'min'         => 1,
                                'max'         => 20,
                                'description' => 'Maximum number of nodes in the autoscaling group.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
