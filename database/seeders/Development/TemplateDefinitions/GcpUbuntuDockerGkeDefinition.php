<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class GcpUbuntuDockerGkeDefinition
{
    public static function get(): array
    {
        return [
            // Only GCP is supported by this template.
            'providers' => ['gcp'],

            // GCP credentials are mandatory.
            'required_providers' => ['gcp'],

            'min_providers' => 1,

            'environment_configuration' => [
                'label'       => 'Ubuntu + Docker + GKE Settings',
                'description' => 'Provisions an Ubuntu 22.04 LTS Compute Engine instance with Docker CE and a Google Kubernetes Engine (GKE) cluster inside a dedicated VPC-native network.',
                'type'        => 'environment',

                'groups' => [
                    [
                        'name'        => 'network',
                        'label'       => 'Network',
                        'description' => 'VPC, subnet and secondary IP ranges shared by the Docker VM and the GKE cluster.',
                        'icon'        => 'network',
                    ],
                    [
                        'name'        => 'docker_vm',
                        'label'       => 'Ubuntu Docker VM',
                        'description' => 'Ubuntu 22.04 LTS Compute Engine instance with Docker CE pre-installed.',
                        'icon'        => 'server',
                    ],
                    [
                        'name'        => 'gke',
                        'label'       => 'GKE Cluster',
                        'description' => 'Google Kubernetes Engine cluster and managed node pool.',
                        'icon'        => 'settings',
                    ],
                ],

                'sections' => [

                    // ── GCP Project / Region ───────────────────────────────────
                    [
                        'name'        => 'cloud',
                        'label'       => 'GCP Project & Region',
                        'description' => 'GCP project and region for all resources.',
                        'group'       => 'network',
                        'fields'      => [
                            [
                                'name'        => 'project_id',
                                'label'       => 'Project ID',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '',
                                'description' => 'GCP project ID where all resources will be created.',
                            ],
                            [
                                'name'     => 'region',
                                'label'    => 'Region',
                                'type'     => 'string',
                                'required' => true,
                                'default'  => 'us-central1',
                            ],
                            [
                                'name'        => 'zone',
                                'label'       => 'Zone (optional)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '',
                                'description' => 'Zone for the Docker VM. Defaults to <region>-a when left blank.',
                            ],
                        ],
                    ],

                    // ── VPC & Subnets ──────────────────────────────────────────
                    [
                        'name'        => 'vpc',
                        'label'       => 'VPC & Subnets',
                        'description' => 'A dedicated VPC-native network with primary and secondary IP ranges (required by GKE alias IPs).',
                        'group'       => 'network',
                        'fields'      => [
                            [
                                'name'        => 'subnet_cidr',
                                'label'       => 'Subnet Primary CIDR',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '10.0.0.0/24',
                                'description' => 'Primary CIDR block for the subnet.',
                            ],
                            [
                                'name'        => 'pods_cidr',
                                'label'       => 'Pods Secondary CIDR',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '10.48.0.0/14',
                                'description' => 'Secondary CIDR range for GKE pods.',
                            ],
                            [
                                'name'        => 'services_cidr',
                                'label'       => 'Services Secondary CIDR',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '10.52.0.0/20',
                                'description' => 'Secondary CIDR range for GKE services.',
                            ],
                        ],
                    ],

                    // ── Ubuntu Docker VM ───────────────────────────────────────
                    [
                        'name'        => 'docker_instance',
                        'label'       => 'Ubuntu Docker Instance',
                        'description' => 'Launches an Ubuntu 22.04 LTS instance and automatically installs Docker CE via startup-script.',
                        'group'       => 'docker_vm',
                        'fields'      => [
                            [
                                'name'        => 'instance_machine_type',
                                'label'       => 'Machine Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'e2-medium',
                                'description' => 'Compute Engine machine type for the Ubuntu Docker VM (e.g. e2-micro, e2-medium, n2-standard-2).',
                            ],
                            [
                                'name'        => 'ssh_public_key',
                                'label'       => 'SSH Public Key (optional)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '',
                                'description' => 'SSH public key in "user:ssh-rsa AAAA..." format. Leave blank to skip SSH access.',
                            ],
                        ],
                    ],

                    // ── GKE Cluster ────────────────────────────────────────────
                    [
                        'name'        => 'gke_cluster',
                        'label'       => 'GKE Cluster',
                        'description' => 'Google Kubernetes Engine cluster configuration.',
                        'group'       => 'gke',
                        'fields'      => [
                            [
                                'name'        => 'cluster_name',
                                'label'       => 'Cluster Name',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'gke',
                                'description' => 'Name suffix for the GKE cluster. The final name is prefixed with the environment ID.',
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
                                'description' => 'Minimum Kubernetes master version for the GKE cluster.',
                            ],
                        ],
                    ],

                    // ── GKE Node Pool ──────────────────────────────────────────
                    [
                        'name'        => 'gke_nodes',
                        'label'       => 'Node Pool',
                        'description' => 'Managed node pool with horizontal autoscaling.',
                        'group'       => 'gke',
                        'fields'      => [
                            [
                                'name'        => 'node_machine_type',
                                'label'       => 'Node Machine Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'e2-medium',
                                'description' => 'Compute Engine machine type for GKE worker nodes.',
                            ],
                            [
                                'name'        => 'desired_node_count',
                                'label'       => 'Desired Node Count',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 2,
                                'min'         => 1,
                                'max'         => 10,
                                'description' => 'Initial / desired number of nodes per zone.',
                            ],
                            [
                                'name'        => 'min_node_count',
                                'label'       => 'Minimum Node Count',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 1,
                                'min'         => 1,
                                'max'         => 10,
                                'description' => 'Minimum number of nodes per zone for the autoscaler.',
                            ],
                            [
                                'name'        => 'max_node_count',
                                'label'       => 'Maximum Node Count',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 3,
                                'min'         => 1,
                                'max'         => 20,
                                'description' => 'Maximum number of nodes per zone for the autoscaler.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
