<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class AkoflowMulticloudDefinition
{
    public static function get(): array
    {
        return [
            'providers'          => ['aws', 'gcp'],
            'required_providers' => ['aws', 'gcp'],

            'environment_configuration' => [
                'label'       => 'AkoFlow Multicloud — EKS + GKE + Server',
                'description' => 'Provisions an Ubuntu EC2 instance (AkoFlow server) on AWS, an EKS cluster on AWS, '
                    . 'and a GKE cluster on GCP. The server automatically configures kubectl for both clusters, '
                    . 'deploys AkoFlow, generates tokens, and writes the .env file.',
                'type'        => 'environment',

                'groups' => [
                    [
                        'name'        => 'aws',
                        'label'       => 'AWS Infrastructure',
                        'description' => 'VPC, AkoFlow server EC2, and EKS cluster on AWS.',
                        'icon'        => 'server',
                    ],
                    [
                        'name'        => 'gcp',
                        'label'       => 'GCP Infrastructure',
                        'description' => 'GKE cluster on Google Cloud.',
                        'icon'        => 'settings',
                    ],
                ],

                'sections' => [

                    // ── AWS Region ─────────────────────────────────────────────
                    [
                        'name'        => 'aws_cloud',
                        'label'       => 'AWS Region',
                        'description' => 'AWS region for all AWS resources.',
                        'group'       => 'aws',
                        'fields'      => [
                            [
                                'name'     => 'aws_region',
                                'label'    => 'AWS Region',
                                'type'     => 'string',
                                'required' => true,
                                'default'  => 'us-east-1',
                            ],
                        ],
                    ],

                    // ── AWS Network ────────────────────────────────────────────
                    [
                        'name'        => 'aws_network',
                        'label'       => 'AWS VPC & Subnets',
                        'description' => 'Dedicated VPC with two public subnets (required by EKS).',
                        'group'       => 'aws',
                        'fields'      => [
                            [
                                'name'    => 'aws_vpc_cidr',
                                'label'   => 'VPC CIDR',
                                'type'    => 'string',
                                'required'=> true,
                                'default' => '10.0.0.0/16',
                            ],
                            [
                                'name'    => 'aws_subnet_1_cidr',
                                'label'   => 'Public Subnet 1 CIDR',
                                'type'    => 'string',
                                'required'=> true,
                                'default' => '10.0.1.0/24',
                            ],
                            [
                                'name'    => 'aws_subnet_2_cidr',
                                'label'   => 'Public Subnet 2 CIDR',
                                'type'    => 'string',
                                'required'=> true,
                                'default' => '10.0.2.0/24',
                            ],
                        ],
                    ],

                    // ── AkoFlow Server (EC2) ───────────────────────────────────
                    [
                        'name'        => 'akoflow_server',
                        'label'       => 'AkoFlow Server',
                        'description' => 'Ubuntu 22.04 EC2 instance that runs AkoFlow and manages both clusters.',
                        'group'       => 'aws',
                        'fields'      => [
                            [
                                'name'        => 'ec2_instance_type',
                                'label'       => 'Instance Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 't3.small',
                                'description' => 'EC2 instance type for the AkoFlow server (e.g. t3.micro, t3.small, t3.medium).',
                            ],
                            [
                                'name'        => 'key_name',
                                'label'       => 'Key Pair Name (optional)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '',
                                'description' => 'Existing EC2 Key Pair name to enable SSH. Leave blank to disable SSH.',
                            ],
                        ],
                    ],

                    // ── EKS Cluster ────────────────────────────────────────────
                    [
                        'name'        => 'eks_cluster',
                        'label'       => 'EKS Cluster',
                        'description' => 'Amazon Elastic Kubernetes Service cluster.',
                        'group'       => 'aws',
                        'fields'      => [
                            [
                                'name'        => 'eks_kubernetes_version',
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
                            ],
                            [
                                'name'        => 'eks_node_instance_type',
                                'label'       => 'Node Instance Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 't3.small',
                                'description' => 'EC2 instance type for EKS workers. Minimum: t3.small. EKS is NOT Free Tier eligible.',
                            ],
                            [
                                'name'     => 'eks_desired_nodes',
                                'label'    => 'Desired Nodes',
                                'type'     => 'number',
                                'required' => true,
                                'default'  => 2,
                                'min'      => 1,
                                'max'      => 10,
                            ],
                            [
                                'name'     => 'eks_min_nodes',
                                'label'    => 'Min Nodes',
                                'type'     => 'number',
                                'required' => true,
                                'default'  => 1,
                                'min'      => 1,
                                'max'      => 10,
                            ],
                            [
                                'name'     => 'eks_max_nodes',
                                'label'    => 'Max Nodes',
                                'type'     => 'number',
                                'required' => true,
                                'default'  => 3,
                                'min'      => 1,
                                'max'      => 20,
                            ],
                        ],
                    ],

                    // ── GCP Project ────────────────────────────────────────────
                    [
                        'name'        => 'gcp_cloud',
                        'label'       => 'GCP Project & Region',
                        'description' => 'GCP project and region for the GKE cluster.',
                        'group'       => 'gcp',
                        'fields'      => [
                            [
                                'name'        => 'gcp_project_id',
                                'label'       => 'Project ID',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '',
                                'description' => 'GCP project ID where the GKE cluster will be created.',
                            ],
                            [
                                'name'    => 'gcp_region',
                                'label'   => 'Region',
                                'type'    => 'string',
                                'required'=> true,
                                'default' => 'us-central1',
                            ],
                        ],
                    ],

                    // ── GCP Credentials ────────────────────────────────────────
                    [
                        'name'        => 'gcp_credentials',
                        'label'       => 'GCP Service Account Key',
                        'description' => 'The JSON key of the GCP service account. '
                            . 'Used by the AkoFlow server to configure kubectl for GKE. '
                            . 'This is the same key used as your GCP provider credential.',
                        'group'       => 'gcp',
                        'fields'      => [
                            [
                                'name'        => 'gcp_sa_key_json',
                                'label'       => 'Service Account JSON Key',
                                'type'        => 'script',
                                'required'    => true,
                                'default'     => '',
                                'description' => 'Paste the full JSON content of your GCP service account key file.',
                            ],
                        ],
                    ],

                    // ── GCP Network ────────────────────────────────────────────
                    [
                        'name'        => 'gcp_network',
                        'label'       => 'GCP VPC & Subnets',
                        'description' => 'VPC-native network with secondary IP ranges (required by GKE).',
                        'group'       => 'gcp',
                        'fields'      => [
                            [
                                'name'    => 'gcp_subnet_cidr',
                                'label'   => 'Subnet Primary CIDR',
                                'type'    => 'string',
                                'required'=> true,
                                'default' => '10.1.0.0/24',
                            ],
                            [
                                'name'        => 'gcp_pods_cidr',
                                'label'       => 'Pods Secondary CIDR',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '10.48.0.0/14',
                                'description' => 'Secondary range for GKE pods.',
                            ],
                            [
                                'name'        => 'gcp_services_cidr',
                                'label'       => 'Services Secondary CIDR',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '10.52.0.0/20',
                                'description' => 'Secondary range for GKE services.',
                            ],
                        ],
                    ],

                    // ── GKE Cluster ────────────────────────────────────────────
                    [
                        'name'        => 'gke_cluster',
                        'label'       => 'GKE Cluster',
                        'description' => 'Google Kubernetes Engine cluster and node pool.',
                        'group'       => 'gcp',
                        'fields'      => [
                            [
                                'name'        => 'gke_kubernetes_version',
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
                            ],
                            [
                                'name'        => 'gke_node_machine_type',
                                'label'       => 'Node Machine Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'e2-medium',
                                'description' => 'Compute Engine machine type for GKE workers (e.g. e2-medium, n2-standard-2).',
                            ],
                            [
                                'name'     => 'gke_desired_nodes',
                                'label'    => 'Desired Nodes',
                                'type'     => 'number',
                                'required' => true,
                                'default'  => 2,
                                'min'      => 1,
                                'max'      => 10,
                            ],
                            [
                                'name'     => 'gke_min_nodes',
                                'label'    => 'Min Nodes',
                                'type'     => 'number',
                                'required' => true,
                                'default'  => 1,
                                'min'      => 1,
                                'max'      => 10,
                            ],
                            [
                                'name'     => 'gke_max_nodes',
                                'label'    => 'Max Nodes',
                                'type'     => 'number',
                                'required' => true,
                                'default'  => 3,
                                'min'      => 1,
                                'max'      => 20,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
