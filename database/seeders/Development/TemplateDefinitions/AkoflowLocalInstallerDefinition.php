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
                'label'       => 'AkôFlow Workflow Engine',
                'description' => 'Installs and manages the AkôFlow workflow engine on a remote host via SSH, Docker and Kind. The host must already have Docker installed.',
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

                    // ── Workflow Engine Settings ──────────────────────────────
                    [
                        'name'        => 'akoflow',
                        'label'       => 'Workflow Engine Settings',
                        'description' => 'Container name and host port used by the AkôFlow workflow engine.',
                        'fields'      => [
                            [
                                'name'        => 'akoflow_workflow_engine_container_name',
                                'label'       => 'Container Name',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'akoflow-engine',
                                'description' => 'Name assigned to the workflow engine container.',
                            ],
                            [
                                'name'        => 'akoflow_workflow_engine_host_port',
                                'label'       => 'Host Port',
                                'type'        => 'number',
                                'required'    => false,
                                'default'     => 18080,
                                'description' => 'Host port mapped to the workflow engine container.',
                            ],
                            [
                                'name'        => 'akoflow_workflow_engine_workspace_dir',
                                'label'       => 'Workspace Path',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '/Users/<username>/akospace',
                                'description' => 'Folder where the .env file will be written.',
                            ],
                        ],
                    ],

                ],
            ],
        ];
    }
}
