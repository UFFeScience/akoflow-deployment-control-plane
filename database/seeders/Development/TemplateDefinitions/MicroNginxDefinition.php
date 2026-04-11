<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class MicroNginxDefinition
{
    public static function get(): array
    {
        return [
            // Declare which cloud providers this template supports.
            'providers' => ['aws', 'gcp'],

            // Providers that are always required (cannot be deselected).
            'required_providers' => [],

            'environment_configuration' => [
                'label'       => 'MicroNGINX Settings',
                'description' => 'Configure your MicroNGINX instance. Settings are split into Deploy Configuration (cloud infrastructure) and NGINX Configuration (server behaviour).',
                'type'        => 'environment',

                // ── Group: Deploy Configuration ───────────────────────────────
                'groups' => [
                    [
                        'name'        => 'deploy',
                        'label'       => 'Deploy Configuration',
                        'description' => 'Cloud infrastructure settings — provider, region, and machine type.',
                        'icon'        => 'server',
                    ],
                    [
                        'name'        => 'nginx',
                        'label'       => 'NGINX Configuration',
                        'description' => 'NGINX server behaviour — written directly to nginx.conf and index.html inside the container.',
                        'icon'        => 'settings',
                    ],
                ],

                'sections' => [

                    // ════════════════════════════════════════════════════════
                    // DEPLOY CONFIGURATION
                    // ════════════════════════════════════════════════════════

                    // ── Cloud Provider ────────────────────────────────────────
                    [
                        'name'        => 'cloud',
                        'label'       => 'Cloud Provider',
                        'description' => 'Provider, region and optional zone / project.',
                        'group'       => 'deploy',
                        'fields'      => [
                            [
                                'name'     => 'region',
                                'label'    => 'Region',
                                'type'     => 'string',
                                'required' => true,
                                'default'  => 'us-east-1',
                            ],
                            [
                                'name'      => 'zone',
                                'label'     => 'Zone / AZ (optional)',
                                'type'      => 'string',
                                'required'  => false,
                                'default'   => '',
                            ],
                            [
                                'name'        => 'project_id',
                                'label'       => 'Project ID (GCP only)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '',
                                'description' => 'Required when deploying to GCP.',
                                'providers'   => ['gcp'],
                            ],
                        ],
                    ],

                    // ── Instance ──────────────────────────────────────────────
                    [
                        'name'        => 'instance',
                        'label'       => 'Instance',
                        'description' => 'Micro-sized machine type for each cloud provider.',
                        'group'       => 'deploy',
                        'fields'      => [
                            [
                                'name'        => 'instance_type',
                                'label'       => 'Instance Type (AWS)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 't3.micro',
                                'description' => 'EC2 instance type used when deploying on AWS.',
                                'providers'   => ['aws'],
                            ],
                            [
                                'name'        => 'machine_type',
                                'label'       => 'Machine Type (GCP)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'e2-micro',
                                'description' => 'Compute Engine machine type used when deploying on GCP.',
                                'providers'   => ['gcp'],
                            ],
                        ],
                    ],

                    // ── SSH Access ─────────────────────────────────────────────
                    [
                        'name'        => 'ssh',
                        'label'       => 'SSH Access',
                        'description' => 'Optional SSH key to allow direct connection to the instance. Leave blank to disable SSH.',
                        'group'       => 'deploy',
                        'fields'      => [
                            [
                                'name'        => 'key_name',
                                'label'       => 'Key Pair Name (AWS only)',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '',
                                'description' => 'Name of an existing EC2 Key Pair used by Ansible SSH. Port 22 is opened in the security group. Example: key-0193e669636e675da',
                                'providers'   => ['aws'],
                            ],
                            [
                                'name'        => 'ssh_public_key',
                                'label'       => 'SSH Public Key (GCP only)',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '',
                                'description' => 'SSH public key for instance metadata. You can paste only the key; username is taken from SSH User when omitted. Port 22 is opened in the firewall.',
                                'providers'   => ['gcp'],
                            ],
                            [
                                'name'        => 'ssh_user',
                                'label'       => 'SSH User (GCP only)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'ubuntu',
                                'description' => 'Linux username used by Ansible and for GCP metadata SSH key entry when not embedded in the public key.',
                                'providers'   => ['gcp'],
                            ],
                        ],
                    ],

                    // ════════════════════════════════════════════════════════
                    // NGINX CONFIGURATION
                    // ════════════════════════════════════════════════════════

                    // ── NGINX Server ──────────────────────────────────────────
                    [
                        'name'        => 'nginx_server',
                        'label'       => 'NGINX Server',
                        'description' => 'Core NGINX directives written to nginx.conf.',
                        'group'       => 'nginx',
                        'fields'      => [
                            [
                                'name'        => 'nginx_port',
                                'label'       => 'Listen Port',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 80,
                                'description' => 'Host port that NGINX listens on. This port is opened in the cloud security group / firewall rule.',
                            ],
                            [
                                'name'        => 'nginx_server_name',
                                'label'       => 'Server Name',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '_',
                                'description' => 'Value of the nginx server_name directive. Use _ to catch all hostnames.',
                            ],
                            [
                                'name'        => 'nginx_worker_processes',
                                'label'       => 'Worker Processes',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'auto',
                                'description' => 'Number of NGINX worker processes. "auto" sets it to the number of available CPU cores.',
                            ],
                            [
                                'name'        => 'nginx_worker_connections',
                                'label'       => 'Worker Connections',
                                'type'        => 'number',
                                'required'    => false,
                                'default'     => 1024,
                                'description' => 'Maximum simultaneous connections each worker process can handle.',
                            ],
                            [
                                'name'        => 'nginx_keepalive_timeout',
                                'label'       => 'Keepalive Timeout (s)',
                                'type'        => 'number',
                                'required'    => false,
                                'default'     => 65,
                                'description' => 'Timeout in seconds for keep-alive connections with the client.',
                            ],
                            [
                                'name'        => 'nginx_client_max_body_size',
                                'label'       => 'Client Max Body Size',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '1m',
                                'description' => 'Maximum allowed size of the client request body (e.g. 1m, 10m, 50m).',
                            ],
                            [
                                'name'        => 'nginx_gzip',
                                'label'       => 'Enable Gzip Compression',
                                'type'        => 'select',
                                'required'    => false,
                                'default'     => 'on',
                                'options'     => [
                                    ['label' => 'On',  'value' => 'on'],
                                    ['label' => 'Off', 'value' => 'off'],
                                ],
                                'description' => 'Enable or disable gzip compression for responses.',
                            ],
                        ],
                    ],

                    // ── HTML Page ─────────────────────────────────────────────
                    [
                        'name'        => 'html_page',
                        'label'       => 'HTML Page',
                        'description' => 'Content served as the default index.html by NGINX.',
                        'group'       => 'nginx',
                        'fields'      => [
                            [
                                'name'        => 'html_page_title',
                                'label'       => 'Page Title',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'Hello from NGINX',
                                'description' => 'Text that appears in the browser tab and as the main heading on the page.',
                            ],
                            [
                                'name'        => 'html_page_body',
                                'label'       => 'Page Body',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'Your MicroNGINX instance is running successfully.',
                                'description' => 'Paragraph text displayed below the heading on the index page.',
                            ],
                        ],
                    ],
                ],
            ],

        ];
    }
}
