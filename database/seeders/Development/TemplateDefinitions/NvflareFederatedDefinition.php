<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class NvflareFederatedDefinition
{
    public static function get(): array
    {
        return [
            'experiment_configuration' => [
                'label'       => 'Experiment Configuration',
                'description' => 'High-level settings shared across all NVIDIA FLARE nodes',
                'type'        => 'experiment',
                'sections'    => [
                    [
                        'name'        => 'nvflare_general',
                        'label'       => 'NVIDIA FLARE General',
                        'description' => 'Global FL experiment parameters',
                        'fields'      => [
                            [
                                'name'        => 'nvflare_version',
                                'label'       => 'NVFLARE Version',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '2.4.0',
                                'description' => 'NVIDIA FLARE Docker image tag to use across all nodes',
                            ],
                            [
                                'name'        => 'fl_rounds',
                                'label'       => 'Federated Learning Rounds',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 100,
                                'min'         => 1,
                                'max'         => 10000,
                                'description' => 'Total number of federated training rounds',
                            ],
                            [
                                'name'     => 'aws_region',
                                'label'    => 'AWS Region',
                                'type'     => 'select',
                                'required' => true,
                                'default'  => 'us-east-1',
                                'options'  => [
                                    ['label' => 'us-east-1',      'value' => 'us-east-1'],
                                    ['label' => 'us-west-2',      'value' => 'us-west-2'],
                                    ['label' => 'eu-west-1',      'value' => 'eu-west-1'],
                                    ['label' => 'ap-southeast-1', 'value' => 'ap-southeast-1'],
                                ],
                            ],
                            [
                                'name'        => 'vpc_cidr',
                                'label'       => 'VPC CIDR Block',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '10.0.0.0/16',
                                'description' => 'CIDR block for the shared VPC / Security Group',
                            ],
                        ],
                    ],
                    [
                        'name'        => 'fl_network',
                        'label'       => 'FL Network',
                        'description' => 'Communication settings between FL participants',
                        'fields'      => [
                            [
                                'name'        => 'server_port',
                                'label'       => 'FL Server Port',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 8002,
                                'min'         => 1024,
                                'max'         => 65535,
                                'description' => 'Port on which the FL Server listens for client connections',
                            ],
                            [
                                'name'        => 'admin_port',
                                'label'       => 'Admin Port',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 8003,
                                'min'         => 1024,
                                'max'         => 65535,
                                'description' => 'Port exposed by the FL Server for admin connections',
                            ],
                            [
                                'name'        => 'overseer_port',
                                'label'       => 'Overseer Port',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 8443,
                                'min'         => 1024,
                                'max'         => 65535,
                                'description' => 'Port on which the Overseer agent listens',
                            ],
                        ],
                    ],
                ],
            ],

            'instance_configurations' => [

                'nvflare-server' => [
                    'label'       => 'FL Server',
                    'description' => 'Central NVIDIA FLARE server that coordinates all federated clients',
                    'type'        => 'instance',
                    'position'    => 1,
                    'sections'    => [
                        [
                            'name'   => 'server_compute',
                            'label'  => 'Compute',
                            'fields' => [
                                [
                                    'name'     => 'instance_type',
                                    'label'    => 'EC2 Instance Type',
                                    'type'     => 'select',
                                    'required' => true,
                                    'default'  => 'c5.2xlarge',
                                    'options'  => [
                                        ['label' => 'c5.xlarge  (4 vCPU, 8 GB)',   'value' => 'c5.xlarge'],
                                        ['label' => 'c5.2xlarge (8 vCPU, 16 GB)',  'value' => 'c5.2xlarge'],
                                        ['label' => 'c5.4xlarge (16 vCPU, 32 GB)', 'value' => 'c5.4xlarge'],
                                    ],
                                ],
                                [
                                    'name'     => 'disk_size_gb',
                                    'label'    => 'EBS Disk Size (GB)',
                                    'type'     => 'number',
                                    'required' => true,
                                    'default'  => 50,
                                    'min'      => 20,
                                    'max'      => 500,
                                ],
                            ],
                        ],
                        [
                            'name'   => 'server_docker',
                            'label'  => 'Docker / NVFLARE',
                            'fields' => [
                                [
                                    'name'        => 'docker_image',
                                    'label'       => 'Docker Image',
                                    'type'        => 'string',
                                    'required'    => true,
                                    'default'     => 'nvflare/nvflare',
                                    'description' => 'Docker image used to run the FL Server',
                                ],
                                [
                                    'name'        => 'docker_command',
                                    'label'       => 'Docker Run Command',
                                    'type'        => 'string',
                                    'required'    => true,
                                    'default'     => 'docker run nvflare',
                                    'description' => 'Command executed to start the FL Server container',
                                ],
                                [
                                    'name'        => 'workspace_dir',
                                    'label'       => 'Workspace Directory',
                                    'type'        => 'string',
                                    'required'    => true,
                                    'default'     => '/opt/nvflare/workspace/server',
                                    'description' => 'Host path mounted as the NVFLARE workspace',
                                ],
                            ],
                        ],
                    ],
                ],

                'nvflare-overseer' => [
                    'label'       => 'Overseer',
                    'description' => 'NVIDIA FLARE Overseer – monitors server availability and drives leader election',
                    'type'        => 'instance',
                    'position'    => 2,
                    'sections'    => [
                        [
                            'name'   => 'overseer_compute',
                            'label'  => 'Compute',
                            'fields' => [
                                [
                                    'name'     => 'instance_type',
                                    'label'    => 'EC2 Instance Type',
                                    'type'     => 'select',
                                    'required' => true,
                                    'default'  => 't3.medium',
                                    'options'  => [
                                        ['label' => 't3.small  (2 vCPU, 2 GB)', 'value' => 't3.small'],
                                        ['label' => 't3.medium (2 vCPU, 4 GB)', 'value' => 't3.medium'],
                                        ['label' => 't3.large  (2 vCPU, 8 GB)', 'value' => 't3.large'],
                                    ],
                                ],
                                [
                                    'name'     => 'disk_size_gb',
                                    'label'    => 'EBS Disk Size (GB)',
                                    'type'     => 'number',
                                    'required' => true,
                                    'default'  => 20,
                                    'min'      => 10,
                                    'max'      => 200,
                                ],
                            ],
                        ],
                        [
                            'name'   => 'overseer_docker',
                            'label'  => 'Docker / NVFLARE',
                            'fields' => [
                                [
                                    'name'     => 'docker_image',
                                    'label'    => 'Docker Image',
                                    'type'     => 'string',
                                    'required' => true,
                                    'default'  => 'nvflare/nvflare',
                                ],
                                [
                                    'name'        => 'docker_command',
                                    'label'       => 'Docker Run Command',
                                    'type'        => 'string',
                                    'required'    => true,
                                    'default'     => 'docker run nvflare',
                                    'description' => 'Command executed to start the Overseer container',
                                ],
                                [
                                    'name'     => 'workspace_dir',
                                    'label'    => 'Workspace Directory',
                                    'type'     => 'string',
                                    'required' => true,
                                    'default'  => '/opt/nvflare/workspace/overseer',
                                ],
                            ],
                        ],
                    ],
                ],

                'nvflare-dfanalyse' => [
                    'label'       => 'DF-Analyse',
                    'description' => 'Data-flow analysis node – aggregates and analyses federated training metrics',
                    'type'        => 'instance',
                    'position'    => 3,
                    'sections'    => [
                        [
                            'name'   => 'dfanalyse_compute',
                            'label'  => 'Compute',
                            'fields' => [
                                [
                                    'name'     => 'instance_type',
                                    'label'    => 'EC2 Instance Type',
                                    'type'     => 'select',
                                    'required' => true,
                                    'default'  => 'r5.xlarge',
                                    'options'  => [
                                        ['label' => 'r5.large   (2 vCPU, 16 GB)', 'value' => 'r5.large'],
                                        ['label' => 'r5.xlarge  (4 vCPU, 32 GB)', 'value' => 'r5.xlarge'],
                                        ['label' => 'r5.2xlarge (8 vCPU, 64 GB)', 'value' => 'r5.2xlarge'],
                                    ],
                                ],
                                [
                                    'name'     => 'disk_size_gb',
                                    'label'    => 'EBS Disk Size (GB)',
                                    'type'     => 'number',
                                    'required' => true,
                                    'default'  => 100,
                                    'min'      => 50,
                                    'max'      => 1000,
                                ],
                            ],
                        ],
                        [
                            'name'   => 'dfanalyse_docker',
                            'label'  => 'Docker / DF-Analyse',
                            'fields' => [
                                [
                                    'name'     => 'docker_image',
                                    'label'    => 'Docker Image',
                                    'type'     => 'string',
                                    'required' => true,
                                    'default'  => 'nvflare/dfanalyse',
                                ],
                                [
                                    'name'        => 'docker_command',
                                    'label'       => 'Docker Run Command',
                                    'type'        => 'string',
                                    'required'    => true,
                                    'default'     => 'docker run dfAnalyse',
                                    'description' => 'Command executed to start the DF-Analyse container',
                                ],
                                [
                                    'name'        => 'output_bucket',
                                    'label'       => 'S3 Output Bucket',
                                    'type'        => 'string',
                                    'required'    => false,
                                    'default'     => '',
                                    'description' => 'S3 bucket where analysis results are stored (optional)',
                                ],
                                [
                                    'name'     => 'workspace_dir',
                                    'label'    => 'Workspace Directory',
                                    'type'     => 'string',
                                    'required' => true,
                                    'default'  => '/opt/nvflare/workspace/dfanalyse',
                                ],
                            ],
                        ],
                    ],
                ],

                'nvflare-site' => [
                    'label'       => 'Federated Site (client)',
                    'description' => 'NVIDIA FLARE federated client – each site holds its own local dataset',
                    'type'        => 'instance',
                    'position'    => 4,
                    'sections'    => [
                        [
                            'name'   => 'site_compute',
                            'label'  => 'Compute',
                            'fields' => [
                                [
                                    'name'     => 'instance_type',
                                    'label'    => 'EC2 Instance Type',
                                    'type'     => 'select',
                                    'required' => true,
                                    'default'  => 'c5.xlarge',
                                    'options'  => [
                                        ['label' => 't3.large   (2 vCPU, 8 GB)',          'value' => 't3.large'],
                                        ['label' => 'c5.xlarge  (4 vCPU, 8 GB)',          'value' => 'c5.xlarge'],
                                        ['label' => 'c5.2xlarge (8 vCPU, 16 GB)',         'value' => 'c5.2xlarge'],
                                        ['label' => 'p3.2xlarge (8 vCPU, 61 GB, 1x V100)', 'value' => 'p3.2xlarge'],
                                    ],
                                ],
                                [
                                    'name'     => 'disk_size_gb',
                                    'label'    => 'EBS Disk Size (GB)',
                                    'type'     => 'number',
                                    'required' => true,
                                    'default'  => 80,
                                    'min'      => 20,
                                    'max'      => 1000,
                                ],
                            ],
                        ],
                        [
                            'name'   => 'site_docker',
                            'label'  => 'Docker / NVFLARE',
                            'fields' => [
                                [
                                    'name'     => 'docker_image',
                                    'label'    => 'Docker Image',
                                    'type'     => 'string',
                                    'required' => true,
                                    'default'  => 'nvflare/nvflare',
                                ],
                                [
                                    'name'        => 'docker_command',
                                    'label'       => 'Docker Run Command',
                                    'type'        => 'string',
                                    'required'    => true,
                                    'default'     => 'docker run nvflare',
                                    'description' => 'Command executed to start the site (client) container',
                                ],
                                [
                                    'name'     => 'workspace_dir',
                                    'label'    => 'Workspace Directory',
                                    'type'     => 'string',
                                    'required' => true,
                                    'default'  => '/opt/nvflare/workspace/site',
                                ],
                                [
                                    'name'        => 'local_dataset_path',
                                    'label'       => 'Local Dataset Path',
                                    'type'        => 'string',
                                    'required'    => false,
                                    'default'     => '/data/local',
                                    'description' => 'Host path to the site-local training dataset',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'cluster_topology' => [
                'description'    => 'NVIDIA FLARE: 1 FL Server · 1 Overseer · 1 DF-Analyse · 10 federated client sites, all on EC2 within a shared Security Group',
                'instance_groups' => [
                    [
                        'name'                         => 'server',
                        'label'                        => 'FL Server',
                        'description'                  => 'Coordinates all federated clients',
                        'instance_group_template_slug' => 'nvflare-server',
                        'quantity'                     => 1,
                        'default_terraform_variables'  => [
                            'instance_type'  => 'c5.2xlarge',
                            'disk_size_gb'   => 50,
                            'docker_image'   => 'nvflare/nvflare',
                            'docker_command' => 'docker run nvflare',
                            'workspace_dir'  => '/opt/nvflare/workspace/server',
                        ],
                    ],
                    [
                        'name'                         => 'overseer',
                        'label'                        => 'Overseer',
                        'description'                  => 'Leader election and server health monitoring',
                        'instance_group_template_slug' => 'nvflare-overseer',
                        'quantity'                     => 1,
                        'default_terraform_variables'  => [
                            'instance_type'  => 't3.medium',
                            'disk_size_gb'   => 20,
                            'docker_image'   => 'nvflare/nvflare',
                            'docker_command' => 'docker run nvflare',
                            'workspace_dir'  => '/opt/nvflare/workspace/overseer',
                        ],
                    ],
                    [
                        'name'                         => 'dfanalyse',
                        'label'                        => 'DF-Analyse',
                        'description'                  => 'Data-flow analysis and metrics aggregation',
                        'instance_group_template_slug' => 'nvflare-dfanalyse',
                        'quantity'                     => 1,
                        'default_terraform_variables'  => [
                            'instance_type'  => 'r5.xlarge',
                            'disk_size_gb'   => 100,
                            'docker_image'   => 'nvflare/dfanalyse',
                            'docker_command' => 'docker run dfAnalyse',
                            'workspace_dir'  => '/opt/nvflare/workspace/dfanalyse',
                            'output_bucket'  => '',
                        ],
                    ],
                    [
                        'name'                         => 'sites',
                        'label'                        => 'Federated Sites (10 clients)',
                        'description'                  => 'Ten independent federated learning client sites',
                        'instance_group_template_slug' => 'nvflare-site',
                        'quantity'                     => 10,
                        'default_terraform_variables'  => [
                            'instance_type'      => 'c5.xlarge',
                            'disk_size_gb'       => 80,
                            'docker_image'       => 'nvflare/nvflare',
                            'docker_command'     => 'docker run nvflare',
                            'workspace_dir'      => '/opt/nvflare/workspace/site',
                            'local_dataset_path' => '/data/local',
                        ],
                    ],
                ],
            ],
        ];
    }
}
