<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class MicroNginxDefinition
{
    public static function get(): array
    {
        return [
            'environment_configuration' => [
                'label'       => 'Deployment Settings',
                'description' => 'Configure your Docker + NGINX micro instance on AWS or GCP.',
                'type'        => 'environment',
                'sections'    => [

                    // ── Cloud Provider ────────────────────────────────────────
                    [
                        'name'        => 'cloud',
                        'label'       => 'Cloud Provider',
                        'description' => 'Provider, region and optional zone / project.',
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
                                'name'        => 'project_id',
                                'label'       => 'Project ID (GCP only)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '',
                                'description' => 'Required when deploying to GCP.',
                            ],
                        ],
                    ],

                    // ── Instance ──────────────────────────────────────────────
                    [
                        'name'        => 'instance',
                        'label'       => 'Instance',
                        'description' => 'Micro-sized machine type for each cloud provider.',
                        'fields'      => [
                            [
                                'name'        => 'instance_type',
                                'label'       => 'Instance Type (AWS)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 't3.micro',
                                'description' => 'EC2 instance type used when deploying on AWS.',
                            ],
                            [
                                'name'        => 'machine_type',
                                'label'       => 'Machine Type (GCP)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'e2-micro',
                                'description' => 'Compute Engine machine type used when deploying on GCP.',
                            ],
                        ],
                    ],

                    // ── NGINX ─────────────────────────────────────────────────
                    [
                        'name'        => 'nginx',
                        'label'       => 'NGINX',
                        'description' => 'NGINX container settings.',
                        'fields'      => [
                            [
                                'name'        => 'nginx_port',
                                'label'       => 'NGINX Port',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 80,
                                'description' => 'Host port NGINX listens on. This port is opened in the cloud security group / firewall rule.',
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
