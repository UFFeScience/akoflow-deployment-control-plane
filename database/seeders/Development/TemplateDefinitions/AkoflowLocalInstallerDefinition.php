<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class AkoflowLocalInstallerDefinition
{
    public static function get(): array
    {
        return [
            'providers'          => ['local'],
            'required_providers' => ['local'],

            'environment_configuration' => [
                'label'       => 'AkôFlow Local Installer',
                'description' => 'Installs AkôFlow on a remote host via SSH using Docker. Requires Docker to be pre-installed on the target host.',
                'type'        => 'environment',

                'sections' => [

                    // ── SSH Connection ────────────────────────────────────────
                    [
                        'name'        => 'connection',
                        'label'       => 'SSH Connection',
                        'description' => 'Host and user for the SSH connection. Must match the values stored in the provider credential.',
                        'fields'      => [
                            [
                                'name'        => 'host',
                                'label'       => 'Host / IP',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'host.docker.internal',
                                'description' => 'Hostname or IP address of the target machine (e.g. 192.168.1.10 or host.docker.internal).',
                            ],
                            [
                                'name'        => 'ssh_user',
                                'label'       => 'SSH User',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'root',
                                'description' => 'SSH username used to connect to the host.',
                            ],
                        ],
                    ],

                    // ── AkôFlow Settings ──────────────────────────────────────
                    [
                        'name'        => 'akoflow',
                        'label'       => 'AkôFlow Settings',
                        'description' => 'Port and workspace directory for the AkôFlow installation.',
                        'fields'      => [
                            [
                                'name'        => 'akoflow_port',
                                'label'       => 'AkôFlow Port',
                                'type'        => 'number',
                                'required'    => false,
                                'default'     => 8080,
                                'description' => 'Host port that AkôFlow will listen on. Must be free on the target machine.',
                            ],
                            [
                                'name'        => 'akospace_dir',
                                'label'       => 'AkoSpace Directory',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '~/akospace',
                                'description' => 'Directory on the host where AkôFlow stores its .env, database and logs.',
                            ],
                        ],
                    ],

                ],
            ],
        ];
    }
}
