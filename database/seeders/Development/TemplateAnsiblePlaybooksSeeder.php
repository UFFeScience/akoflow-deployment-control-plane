<?php

namespace Database\Seeders\Development;

use App\Models\AnsiblePlaybook;
use App\Models\AnsiblePlaybookTask;
use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateProviderConfiguration;
use App\Models\EnvironmentTemplateVersion;
use Illuminate\Database\Seeder;

class TemplateAnsiblePlaybooksSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->playbooks() as $entry) {
            $versionId = $this->resolveVersionId($entry['template_slug'], $entry['template_version']);

            if ($versionId === null) {
                $this->command->warn(
                    "Skipping activity '{$entry['name']}': template '{$entry['template_slug']}' v{$entry['template_version']} not found."
                );
                continue;
            }

            $providerType = strtoupper($entry['provider_type']);

            $config = EnvironmentTemplateProviderConfiguration::firstOrCreate(
                ['template_version_id' => $versionId, 'name' => $providerType],
                ['applies_to_providers' => [$providerType]],
            );

            $activity = AnsiblePlaybook::updateOrCreate(
                [
                    'provider_configuration_id' => $config->id,
                    'name'                      => $entry['name'],
                    'trigger'                   => $entry['trigger'],
                ],
                [
                    'description'          => $entry['description'] ?? null,
                    'playbook_slug'        => $entry['playbook_slug'] ?? null,
                    'playbook_yaml'        => $entry['playbook_yaml'] ?? null,
                    'inventory_template'   => $entry['inventory_template'] ?? null,
                    'vars_mapping_json'    => $entry['vars_mapping_json'] ?? [],
                    'outputs_mapping_json' => $entry['outputs_mapping_json'] ?? [],
                    'credential_env_keys'  => $entry['credential_env_keys'] ?? [],
                    'roles_json'           => $entry['roles_json'] ?? [],
                    'position'             => $entry['position'] ?? 0,
                    'enabled'              => $entry['enabled'] ?? true,
                ]
            );

            $this->syncTasks($activity, $entry['tasks'] ?? []);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function resolveVersionId(string $templateSlug, string $version): ?int
    {
        $template = EnvironmentTemplate::where('slug', $templateSlug)->first();

        if (!$template) {
            return null;
        }

        return EnvironmentTemplateVersion::where('template_id', $template->id)
            ->where('version', $version)
            ->value('id');
    }

    private function syncTasks(AnsiblePlaybook $activity, array $tasks): void
    {
        $activity->tasks()->delete();

        foreach (array_values($tasks) as $position => $taskData) {
            AnsiblePlaybookTask::create([
                'ansible_playbook_id' => $activity->id,
                'position'            => $taskData['position'] ?? $position,
                'name'                => $taskData['name'],
                'module'              => $taskData['module'] ?? null,
                'module_args_json'    => $taskData['module_args_json'] ?? null,
                'when_condition'      => $taskData['when_condition'] ?? null,
                'become'              => $taskData['become'] ?? false,
                'tags_json'           => $taskData['tags_json'] ?? null,
                'enabled'             => $taskData['enabled'] ?? true,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Activity definitions
    // ─────────────────────────────────────────────────────────────────────────

    private function playbooks(): array
    {
        return [
        // ── Micro Docker + NGINX (AWS / GCP) ─────────────────────────
        $this->microNginxAwsInstallAndConfigure(),
        $this->microNginxGcpInstallAndConfigure(),

            // ── AWS Docker Install ──────────────────────────────────────────
            $this->awsDockerInstall(),
            $this->awsDockerValidate(),
            $this->awsDockerRestart(),
            $this->awsDockerShutdown(),

            // ── NVIDIA Flare Federated Learning ────────────────────────────
            $this->sscad2025Bootstrap(),

            // ── AkôFlow Multicloud Demo ────────────────────────────────────
            $this->akoflowMulticloudBootstrap(),
            $this->akoflowMulticloudEngineStart(),
            $this->akoflowMulticloudEngineRestart(),
            $this->akoflowMulticloudEngineStop(),

            // ── AkôFlow Local Installer ────────────────────────────────────
            $this->localAkoflowInstall(),
            $this->localAkoflowStart(),
            $this->localAkoflowRestart(),
            $this->localAkoflowStop(),
            $this->localAkoflowTeardown(),
        ];
    }

    // ─── AWS Docker ──────────────────────────────────────────────────────────

    private function microNginxAwsInstallAndConfigure(): array
    {
        return [
            'template_slug'    => 'micro-nginx',
            'template_version' => '1.0.0',
            'provider_type'    => 'aws',
            'name'             => 'Install Docker and Configure NGINX',
            'description'      => 'Installs Docker and deploys a configured NGINX container on the provisioned VM.',
            'trigger'          => AnsiblePlaybook::TRIGGER_AFTER_PROVISION,
            'position'         => 0,
            'playbook_slug'    => 'micro-nginx-aws-bootstrap',
            'inventory_template' => null,
            'playbook_yaml'    => <<<'YAML'
- name: Install Docker and configure NGINX container
  hosts: all
  become: true
  gather_facts: false
  vars:
    nginx_port: "{{ nginx_port | default(80) }}"
    nginx_server_name: "{{ nginx_server_name | default('_') }}"
    nginx_worker_processes: "{{ nginx_worker_processes | default('auto') }}"
    nginx_worker_connections: "{{ nginx_worker_connections | default(1024) }}"
    nginx_keepalive_timeout: "{{ nginx_keepalive_timeout | default(65) }}"
    nginx_client_max_body_size: "{{ nginx_client_max_body_size | default('1m') }}"
    nginx_gzip: "{{ nginx_gzip | default('on') }}"
    html_page_title: "{{ html_page_title | default('Hello from NGINX') }}"
    html_page_body: "{{ html_page_body | default('Your MicroNGINX instance is running successfully.') }}"

  pre_tasks:
    - name: Wait for SSH to become ready
      wait_for_connection:
        delay: 5
        timeout: 300

    - name: Gather facts after SSH is available
      setup:

  tasks:
    - name: Update apt cache
      apt:
        update_cache: yes
        cache_valid_time: 3600

    - name: Install Docker runtime packages
      apt:
        name:
          - docker.io
        state: present

    - name: Enable and start Docker service
      systemd:
        name: docker
        state: started
        enabled: yes

    - name: Create workspace directory for NGINX configuration
      file:
        path: /opt/micro-nginx
        state: directory
        mode: '0755'

    - name: Write nginx.conf
      copy:
        dest: /opt/micro-nginx/nginx.conf
        mode: '0644'
        content: |
          worker_processes {{ nginx_worker_processes }};

          events {
              worker_connections {{ nginx_worker_connections }};
          }

          http {
              sendfile on;
              keepalive_timeout {{ nginx_keepalive_timeout }};
              client_max_body_size {{ nginx_client_max_body_size }};
              gzip {{ nginx_gzip }};

              server {
                  listen {{ nginx_port }};
                  server_name {{ nginx_server_name }};

                  location / {
                      root /usr/share/nginx/html;
                      index index.html;
                  }
              }
          }

    - name: Write index.html
      copy:
        dest: /opt/micro-nginx/index.html
        mode: '0644'
        content: |
          <!DOCTYPE html>
          <html>
          <head>
            <meta charset="utf-8" />
            <title>{{ html_page_title }}</title>
          </head>
          <body>
            <h1>{{ html_page_title }}</h1>
            <p>{{ html_page_body }}</p>
          </body>
          </html>

    - name: Remove existing micro-nginx container if present
      shell: docker rm -f micro-nginx || true
      changed_when: false

    - name: Run configured NGINX container
      shell: |
        docker run -d \
          --name micro-nginx \
          --restart unless-stopped \
          -p {{ nginx_port }}:{{ nginx_port }} \
          -v /opt/micro-nginx/nginx.conf:/etc/nginx/nginx.conf:ro \
          -v /opt/micro-nginx/index.html:/usr/share/nginx/html/index.html:ro \
          nginx:stable-alpine

    - name: Write ansible_outputs.json
      copy:
        dest: /tmp/ansible_outputs.json
        content: |
          {
            "nginx_url": "http://{{ ansible_host }}:{{ nginx_port }}",
            "container_name": "micro-nginx"
          }
YAML,
            'vars_mapping_json'    => [
                'environment_configuration' => [
                  'ssh_user'                   => 'ansible_user',
                    'nginx_port'                 => ['ansible_var' => 'nginx_port', 'cast' => 'int'],
                    'nginx_server_name'          => 'nginx_server_name',
                    'nginx_worker_processes'     => 'nginx_worker_processes',
                    'nginx_worker_connections'   => ['ansible_var' => 'nginx_worker_connections', 'cast' => 'int'],
                    'nginx_keepalive_timeout'    => ['ansible_var' => 'nginx_keepalive_timeout', 'cast' => 'int'],
                    'nginx_client_max_body_size' => 'nginx_client_max_body_size',
                    'nginx_gzip'                 => 'nginx_gzip',
                    'html_page_title'            => 'html_page_title',
                    'html_page_body'             => 'html_page_body',
                ],
            ],
            'outputs_mapping_json' => [
                'resources' => [[
                    'name'                  => 'nginx-vm',
                    'ansible_resource_type' => 'nginx_config',
                    'outputs'               => [
                        'metadata' => [
                            'nginx_url'      => 'nginx_url',
                            'container_name' => 'container_name',
                        ],
                    ],
                ]],
            ],
            'credential_env_keys' => ['SSH_PRIVATE_KEY'],
            'roles_json'          => [],
            'tasks'               => [
              ['name' => 'Wait for SSH to become ready', 'module' => 'wait_for_connection'],
              ['name' => 'Gather facts after SSH is available', 'module' => 'setup'],
                ['name' => 'Update apt cache', 'module' => 'apt'],
                ['name' => 'Install Docker runtime packages', 'module' => 'apt'],
                ['name' => 'Enable and start Docker service', 'module' => 'systemd'],
                ['name' => 'Create workspace directory for NGINX configuration', 'module' => 'file'],
                ['name' => 'Write nginx.conf', 'module' => 'copy'],
                ['name' => 'Write index.html', 'module' => 'copy'],
                ['name' => 'Remove existing micro-nginx container if present', 'module' => 'shell'],
                ['name' => 'Run configured NGINX container', 'module' => 'shell'],
                ['name' => 'Write ansible_outputs.json', 'module' => 'copy'],
            ],
        ];
    }

    private function microNginxGcpInstallAndConfigure(): array
    {
        $entry = $this->microNginxAwsInstallAndConfigure();
        $entry['provider_type'] = 'gcp';
        $entry['playbook_slug'] = 'micro-nginx-gcp-bootstrap';

        return $entry;
    }

    private function awsDockerInstall(): array
    {
        return [
            'template_slug'    => 'aws-docker-ansible',
            'template_version' => '1.0.0',
            'provider_type'    => 'aws',
            'name'             => 'Install Docker',
            'description'      => 'Installs Docker Engine and Compose plugin on the provisioned EC2 instance.',
            'trigger'          => AnsiblePlaybook::TRIGGER_AFTER_PROVISION,
            'position'         => 0,
            'playbook_slug'    => 'aws-docker-install',
            'inventory_template' => null,
            'playbook_yaml'    => <<<'YAML'
- name: Install Docker on AWS EC2 instance
  hosts: all
  become: true

  tasks:
    - name: Update apt cache
      apt:
        update_cache: yes
        cache_valid_time: 3600

    - name: Install prerequisites
      apt:
        name:
          - ca-certificates
          - curl
          - gnupg
          - lsb-release
        state: present

    - name: Add Docker GPG key
      apt_key:
        url: https://download.docker.com/linux/ubuntu/gpg
        state: present

    - name: Add Docker APT repository
      apt_repository:
        repo: "deb [arch=amd64] https://download.docker.com/linux/ubuntu {{ ansible_distribution_release }} stable"
        state: present
        update_cache: yes

    - name: Install Docker Engine
      apt:
        name:
          - docker-ce
          - docker-ce-cli
          - containerd.io
        state: present

    - name: Start and enable Docker service
      systemd:
        name: docker
        state: started
        enabled: yes

    - name: Write bootstrap ansible_outputs.json
      copy:
        dest: /tmp/ansible_outputs.json
        content: |
          {
            "docker_version": "{{ ansible_facts.packages['docker-ce'][0].version | default('installed') }}"
          }
YAML,
            'vars_mapping_json'    => ['environment_configuration' => ['ssh_user' => 'ansible_user']],
            'outputs_mapping_json' => [
                'resources' => [[
                    'name'                  => 'docker-host',
                    'ansible_resource_type' => 'docker_install',
                    'outputs'               => ['metadata' => ['docker_version' => 'docker_version']],
                ]],
            ],
            'credential_env_keys' => ['SSH_PRIVATE_KEY'],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Update apt cache', 'module' => 'apt'],
                ['name' => 'Install prerequisites', 'module' => 'apt'],
                ['name' => 'Add Docker GPG key', 'module' => 'apt_key'],
                ['name' => 'Add Docker APT repository', 'module' => 'apt_repository'],
                ['name' => 'Install Docker Engine', 'module' => 'apt'],
                ['name' => 'Start and enable Docker service', 'module' => 'systemd'],
                ['name' => 'Write ansible_outputs.json', 'module' => 'copy'],
            ],
        ];
    }

    private function awsDockerRestart(): array
    {
        return [
            'template_slug'    => 'aws-docker-ansible',
            'template_version' => '1.0.0',
            'provider_type'    => 'aws',
            'name'             => 'Restart Docker',
            'description'      => 'Restarts the Docker daemon on the provisioned instance.',
            'trigger'          => AnsiblePlaybook::TRIGGER_MANUAL,
            'position'         => 0,
            'playbook_yaml'    => <<<'YAML'
- name: Restart Docker
  hosts: all
  become: true

  tasks:
    - name: Restart Docker service
      systemd:
        name: docker
        state: restarted
YAML,
            'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user']],
            'credential_env_keys' => ['SSH_PRIVATE_KEY'],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Restart Docker service', 'module' => 'systemd'],
            ],
        ];
    }

    private function awsDockerValidate(): array
    {
        return [
            'template_slug'    => 'aws-docker-ansible',
            'template_version' => '1.0.0',
            'provider_type'    => 'aws',
            'name'             => 'Validate Docker',
            'description'      => 'Verifies Docker is available once the environment is ready.',
            'trigger'          => AnsiblePlaybook::TRIGGER_WHEN_READY,
            'position'         => 1,
            'playbook_slug'    => 'aws-docker-validate',
            'playbook_yaml'    => <<<'YAML'
- name: Validate Docker on AWS EC2 instance
  hosts: all
  become: true

  tasks:
    - name: Check Docker version
      shell: docker --version
      register: docker_version
      changed_when: false
      failed_when: docker_version.rc != 0
YAML,
            'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user']],
            'credential_env_keys' => ['SSH_PRIVATE_KEY'],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Check Docker version', 'module' => 'shell'],
            ],
        ];
    }

    private function awsDockerShutdown(): array
    {
        return [
            'template_slug'    => 'aws-docker-ansible',
            'template_version' => '1.0.0',
            'provider_type'    => 'aws',
            'name'             => 'Stop Docker',
            'description'      => 'Stops the Docker service before the instance is torn down.',
            'trigger'          => AnsiblePlaybook::TRIGGER_BEFORE_TEARDOWN,
            'position'         => 0,
            'playbook_slug'    => 'aws-docker-shutdown',
            'playbook_yaml'    => <<<'YAML'
- name: Stop Docker on AWS EC2 instance
  hosts: all
  become: true

  tasks:
    - name: Stop Docker service
      systemd:
        name: docker
        state: stopped
YAML,
            'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user']],
            'credential_env_keys' => ['SSH_PRIVATE_KEY'],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Stop Docker service', 'module' => 'systemd'],
            ],
        ];
    }

    // ─── NVIDIA Flare Federated Learning ─────────────────────────────────────

    private function sscad2025Bootstrap(): array
    {
        $siteResources = [];
        for ($i = 1; $i <= 10; $i++) {
            $siteResources[] = [
                'name'             => "site-{$i}",
                'terraform_type'   => 'google_compute_instance',
                'outputs'          => [
                    'provider_resource_id' => "site_{$i}_name",
                    'public_ip'            => "site_{$i}_public_ip",
                    'private_ip'           => "site_{$i}_private_ip",
                    'metadata'             => ['role' => 'site'],
                ],
            ];
        }

        return [
            'template_slug'    => 'nvidia-flare-federated-learning',
            'template_version' => '1.0.0',
            'provider_type'    => 'gcp',
            'name'             => 'NVIDIA Flare Bootstrap & Execution',
            'description'      => 'Bootstraps all nodes, starts the NVIDIA FLARE federation (DfAnalyse, Overseer, Server, Sites) and submits the experiment job once the environment is ready.',
            'trigger'          => AnsiblePlaybook::TRIGGER_AFTER_PROVISION,
            'position'         => 0,
            'playbook_slug'    => 'nvidia-flare-federated-learning-bootstrap',
            'inventory_template' => null,
            'playbook_yaml'    => <<<'YAML'
- name: NVIDIA Flare bootstrap
  hosts: all
  become: true
  gather_facts: false

  pre_tasks:
    - name: Wait for SSH to become ready
      wait_for_connection:
        connect_timeout: 15
        delay: 10
        sleep: 10
        timeout: 600

    - name: Wait for cloud-init and apt locks to be released
      raw: |
        cloud-init status --wait 2>/dev/null || true
        while fuser /var/lib/dpkg/lock-frontend >/dev/null 2>&1 || fuser /var/lib/apt/lists/lock >/dev/null 2>&1; do sleep 3; done
      changed_when: false

    - name: Gather facts
      setup:

  tasks:
    - name: Install Docker and execution prerequisites
      apt:
        name:
          - docker.io
          - unzip
          - wget
          - curl
          - git
          - jq
          - python3
          - python3-pip
        state: present
        update_cache: yes

    - name: Enable and start Docker
      systemd:
        name: docker
        state: started
        enabled: yes

    - name: Add ubuntu to docker group
      shell: usermod -aG docker ubuntu || true
      changed_when: false

    - name: Prepare execution directory
      file:
        path: /opt/sscad
        state: directory
        mode: '0755'

    - name: Write execution manifest
      copy:
        dest: /opt/sscad/execution-manifest.json
        content: |
          {
            "experiment_name": "{{ experiment_name }}",
            "description": "{{ description }}",
            "algorithm": "{{ algorithm }}",
            "clients": {{ clients }},
            "dataset_folder_key": "{{ dataset_folder_key }}",
            "site_folder_url": "{{ site_folder_url }}"
          }

    - name: Write post-execution ansible_outputs.json
      copy:
        dest: /tmp/ansible_outputs.json
        content: |
          {
            "execution_name": "{{ experiment_name }}",
            "execution_status": "bootstrap-ready",
            "execution_order": "bootstrap,dfanalyse,overseer,server,sites,pos",
            "bootstrap_dir": "/opt/sscad",
            "algorithm": "{{ algorithm }}",
            "clients": "{{ clients }}"
          }

- name: NVIDIA Flare startup DfAnalyse
  hosts: dfanalyse
  become: true
  gather_facts: false

  pre_tasks:
    - name: Wait for SSH to become ready
      wait_for_connection:
        connect_timeout: 15
        delay: 10
        sleep: 10
        timeout: 600

    - name: Gather facts
      setup:

  tasks:
    - name: Install DfAnalyse dependencies
      apt:
        name:
          - unzip
          - wget
        state: present
        update_cache: yes

    - name: Prepare DfAnalyse workspace
      file:
        path: /DfAnalyzer
        state: directory
        mode: '0755'

    - name: Download DfAnalyse source
      shell: |
        set -eux
        cd /DfAnalyzer
        wget https://github.com/nymeria-42/federated_clustering/archive/refs/heads/main.zip -O main.zip
        unzip -o main.zip
        mv federated_clustering-main/dfanalyzer/ /DfAnalyzer
        rm -rf main.zip federated_clustering-main
      changed_when: false

    - name: Start DfAnalyse container
      shell: |
        set -eux
        cd /DfAnalyzer
        docker run --rm --name dfanalyzer -d -p 22000:22000 -e DFA_URL=http://0.0.0.0 -w /DfAnalyzer -v $(pwd)/save_results.sql:/DfAnalyzer/save_results.sql -v $(pwd)/data:/DfAnalyzer/data -v $(pwd)/results:/DfAnalyzer/results nymeria0042/dfanalyzer sh start-dfanalyzer.sh
      changed_when: false

- name: NVIDIA Flare startup Overseer
  hosts: overseer
  become: true
  gather_facts: false

  pre_tasks:
    - name: Wait for SSH to become ready
      wait_for_connection:
        connect_timeout: 15
        delay: 10
        sleep: 10
        timeout: 600

    - name: Gather facts
      setup:

  tasks:
    - name: Install Overseer dependencies
      apt:
        name:
          - unzip
          - wget
        state: present
        update_cache: yes

    - name: Prepare Overseer workspace
      file:
        path: /fed-clustering
        state: directory
        mode: '0755'

    - name: Download Overseer source and resources
      shell: |
        set -eux
        cd /fed-clustering
        wget https://github.com/nymeria-42/federated_clustering/archive/refs/heads/main.zip -O main.zip
        unzip -o main.zip
        mv federated_clustering-main/fed-clustering/ /fed-clustering
        rm -rf main.zip federated_clustering-main
        wget https://storage.googleapis.com/outliers-ccpe-2026/prod_01.zip -O prod_01.zip
        unzip -o prod_01.zip
        cp -R prod_01/overseer ./
        rm -rf prod_01 prod_01.zip
        mkdir -p overseer/local overseer/transfer
        chmod -R 777 overseer
      changed_when: false

    - name: Start Overseer container
      shell: |
        set -eux
        cd /fed-clustering
        docker run -d --rm --name overseer --add-host overseer:$(hostname -I | awk '{print $1}') -p 8443:8443 -v $(pwd)/overseer:/workspace -e WORKSPACE=/workspace -e IMAGE_NAME=ovvesley/nvflare-service:latest ovvesley/nvflare-service:latest /workspace/startup/start.sh
      changed_when: false

- name: NVIDIA Flare startup Server
  hosts: server
  become: true
  gather_facts: false
  vars:
    dfa_host: "{{ hostvars['dfanalyse'].ansible_host }}"
    overseer_host: "{{ hostvars['overseer'].ansible_host }}"
    algorithm: "{{ algorithm }}"

  pre_tasks:
    - name: Wait for SSH to become ready
      wait_for_connection:
        connect_timeout: 15
        delay: 10
        sleep: 10
        timeout: 600

    - name: Gather facts
      setup:

  tasks:
    - name: Install Server dependencies
      apt:
        name:
          - unzip
          - wget
        state: present
        update_cache: yes

    - name: Prepare server workspace
      file:
        path: /fed-clustering
        state: directory
        mode: '0755'

    - name: Download Server source and resources
      shell: |
        set -eux
        cd /fed-clustering
        wget https://github.com/nymeria-42/federated_clustering/archive/refs/heads/main.zip -O main.zip
        unzip -o main.zip
        mv federated_clustering-main/fed-clustering/ /fed-clustering
        rm -rf main.zip federated_clustering-main
        wget https://storage.googleapis.com/outliers-ccpe-2026/infra-sscad-2/prod_01.zip -O prod_01.zip
        unzip -o prod_01.zip
        cp -R prod_01/server1 ./
        rm -rf prod_01 prod_01.zip
        mkdir -p server1/admin
        wget https://storage.googleapis.com/outliers-ccpe-2026/infra-sscad-2/prod_01.zip -O prod_01.zip
        unzip -o prod_01.zip
        cp -R prod_01/admin@nvidia.com ./
        rm -rf prod_01 prod_01.zip
        mv admin@nvidia.com/* server1/admin/
        rm -rf admin@nvidia.com
        chmod -R 777 server1
      changed_when: false

    - name: Generate prospective provenance
      shell: |
        set -eux
        cd /fed-clustering
        sleep 10
        docker run --rm --add-host overseer:{{ overseer_host }} -e DFA_URL=http://{{ dfa_host }}:22000 -e PYTHON_EXECUTABLE=python3 ovvesley/nvflare-service:latest bash -c "python /fed-clustering/utils/prospective_provenance.py --algorithm {{ algorithm }}"
      changed_when: false

    - name: Start Server container
      shell: |
        set -eux
        cd /fed-clustering
        echo "{{ overseer_host }} overseer" >> /etc/hosts
        docker run -d --rm --name server1 -v $(pwd)/server1:/workspace -v nvflare_svc_persist:/tmp/nvflare/ --add-host overseer:{{ overseer_host }} --add-host server1:$(hostname -I | awk '{print $1}') -e DFA_URL=http://{{ dfa_host }}:22000 -p 8002:8002 -p 8003:8003 -e PYTHON_EXECUTABLE=python3 -e WORKSPACE=/workspace -e IMAGE_NAME=ovvesley/nvflare-service:latest -w /workspace/server1 ovvesley/nvflare-service:latest python -u -m nvflare.private.fed.app.server.server_train -m /workspace -s fed_server.json --set secure_train=true config_folder=config org=nvidia
      changed_when: false

- name: NVIDIA Flare startup Sites
  hosts: all
  become: true
  gather_facts: false
  vars:
    server_host: "{{ hostvars['server'].ansible_host }}"
    overseer_host: "{{ hostvars['overseer'].ansible_host }}"
    dfa_host: "{{ hostvars['dfanalyse'].ansible_host }}"

  pre_tasks:
    - name: Wait for SSH to become ready
      wait_for_connection:
        connect_timeout: 15
        delay: 10
        sleep: 10
        timeout: 600

    - name: Gather facts
      setup:

  tasks:
    - name: Install site dependencies
      apt:
        name:
          - unzip
          - wget
        state: present
        update_cache: yes
      when: inventory_hostname is match('^site-')

    - name: Prepare site workspace
      file:
        path: /fed-clustering
        state: directory
        mode: '0755'
      when: inventory_hostname is match('^site-')

    - name: Download federated clustering source
      shell: |
        set -eux
        cd /fed-clustering
        wget https://github.com/nymeria-42/federated_clustering/archive/refs/heads/main.zip -O main.zip
        unzip -o main.zip
        mv federated_clustering-main/fed-clustering/ /fed-clustering
        rm -rf main.zip federated_clustering-main
      changed_when: false
      when: inventory_hostname is match('^site-')

    - name: Download site workspace package
      shell: |
        set -eux
        cd /fed-clustering
        wget {{ site_folder_url }} -O prod_01.zip
        unzip -o prod_01.zip
        cp -R prod_01/{{ inventory_hostname }} ./
        rm -rf prod_01 prod_01.zip
        chmod -R 777 {{ inventory_hostname }}
      changed_when: false
      when: inventory_hostname is match('^site-')

    - name: Download dataset file for site
      shell: |
        set -eux
        site_index=$(echo "{{ inventory_hostname }}" | sed 's/^site-//')
        dataset_file="des_$((site_index - 1)).csv"
        wget "{{ dataset_folder_key }}/$dataset_file" -O /fed-clustering/des.csv
      changed_when: false
      when: inventory_hostname is match('^site-')

    - name: Wait before site dataset preprocessing
      shell: |
        set -eux
        sleep 10
      changed_when: false
      when: inventory_hostname is match('^site-')

    - name: Prepare site dataset for NVFlare
      shell: |
        set -eux
        cd /fed-clustering
        docker run --rm -v $(pwd)/des.csv:/tmp/des.csv -v $(pwd)/output:/tmp/nvflare --add-host overseer:{{ overseer_host }} --add-host server1:{{ server_host }} -e DFA_URL=http://{{ dfa_host }}:22000 -e PYTHON_EXECUTABLE=python3 ovvesley/nvflare-service:latest bash -c "python3 /fed-clustering/utils/prepare_data.py --input_csv /tmp/des.csv --randomize 1 --out_path /tmp/nvflare/dataset/des.csv"
      changed_when: false
      when: inventory_hostname is match('^site-')

    - name: Write hosts mapping for site
      shell: |
        set -eux
        echo "{{ overseer_host }} overseer" >> /etc/hosts
        echo "{{ server_host }} server" >> /etc/hosts
      changed_when: false
      when: inventory_hostname is match('^site-')

    - name: Start site container
      shell: |
        set -eux
        cd /fed-clustering
        site_name={{ inventory_hostname }}
        docker run -d --rm --name "$site_name" -v $(pwd)/$site_name:/workspace -v $(pwd)/output/:/tmp/nvflare/ --add-host overseer:{{ overseer_host }} --add-host server1:{{ server_host }} -e DFA_URL=http://{{ dfa_host }}:22000 -e PYTHON_EXECUTABLE=python3 -e WORKSPACE=/workspace -e IMAGE_NAME=ovvesley/nvflare-service:latest -w /workspace/$site_name ovvesley/nvflare-service:latest python -u -m nvflare.private.fed.app.client.client_train -m /workspace -s fed_client.json --set secure_train=true uid=$site_name org=nvidia config_folder=config
      changed_when: false
      when: inventory_hostname is match('^site-')

- name: NVIDIA Flare post execution on server
  hosts: server
  become: true
  vars:
    dfa_host: "{{ hostvars['dfanalyse'].ansible_host }}"
    algorithm: "{{ algorithm }}"
    clients: "{{ clients }}"

  tasks:
    - name: Wait for DfAnalyse availability
      wait_for:
        host: "{{ dfa_host }}"
        port: 22000
        state: started
        timeout: 600

    - name: Adjust server round count
      shell: |
        set -eux
        sudo docker exec -e ALGORITHM="{{ algorithm }}" server1 bash -c '
          export PYTHON_EXECUTABLE=python3
          export WORKSPACE=/workspace
          export IMAGE_NAME=ovvesley/nvflare-service:latest
          sed -i -e "s/\"num_rounds\": *100/\"num_rounds\": 5/" /fed-clustering/jobs/sklearn_${ALGORITHM}_base/app/config/config_fed_server.json
        '
      changed_when: false

    - name: Prepare job config
      shell: |
        set -eux
        sudo docker exec -e ALGORITHM="{{ algorithm }}" -e CLIENTS="{{ clients }}" server1 bash -c '
          export PYTHON_EXECUTABLE=python3
          export WORKSPACE=/workspace
          export IMAGE_NAME=ovvesley/nvflare-service:latest
          sed -i -e "s/NUM_CLIENTS=20/NUM_CLIENTS=${CLIENTS}/" /fed-clustering/prepare_job_config.sh
          sed -i -e "s/sklearn_kmeans/sklearn_${ALGORITHM}/" /fed-clustering/prepare_job_config.sh
          cd /fed-clustering
          source prepare_job_config.sh
          cp -R /fed-clustering/jobs/* /workspace/admin/transfer/
        '
      changed_when: false

    - name: Submit NVFlare job
      shell: |
        set -eux
        printf 'admin@nvidia.com\ncheck_status server\nsubmit_job sklearn_{{ algorithm }}_{{ clients }}\ncheck_status server\n' | sudo docker exec -i server1 /workspace/admin/startup/fl_admin.sh
      changed_when: false

    - name: Write ansible_outputs.json
      copy:
        dest: /tmp/ansible_outputs.json
        content: |
          {
            "execution_name": "{{ inventory_hostname }}",
            "execution_status": "pos-complete",
            "algorithm": "{{ algorithm }}",
            "clients": "{{ clients }}"
          }
YAML,
            'vars_mapping_json'    => [
                'environment_configuration' => [
                    'ssh_user'          => 'ansible_user',
                    'experiment_name'   => 'experiment_name',
                    'description'       => 'description',
                    'algorithm'         => 'algorithm',
                    'clients'           => 'clients',
                    'dataset_folder_key' => 'dataset_folder_key',
                    'site_folder_url'   => 'site_folder_url',
                ],
            ],
            'outputs_mapping_json' => [
                'resources' => [
                    [
                        'name'    => 'sscad-execution',
                        'outputs' => [
                            'provider_resource_id' => 'execution_name',
                            'metadata'             => [
                                'execution_status' => 'execution_status',
                                'execution_order'  => 'execution_order',
                                'bootstrap_dir'    => 'bootstrap_dir',
                                'algorithm'        => 'algorithm',
                                'clients'          => 'clients',
                            ],
                        ],
                    ],
                    ...$siteResources,
                ],
            ],
            'credential_env_keys' => ['SSH_PRIVATE_KEY'],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Install Docker and execution prerequisites', 'module' => 'apt'],
                ['name' => 'Enable and start Docker', 'module' => 'systemd'],
                ['name' => 'Add ubuntu to docker group', 'module' => 'shell'],
                ['name' => 'Prepare execution directory', 'module' => 'file'],
                ['name' => 'Write execution manifest', 'module' => 'copy'],
                ['name' => 'Write bootstrap ansible_outputs.json', 'module' => 'copy'],
                ['name' => 'Install DfAnalyse dependencies', 'module' => 'apt'],
                ['name' => 'Prepare DfAnalyse workspace', 'module' => 'file'],
                ['name' => 'Download DfAnalyse source', 'module' => 'shell'],
                ['name' => 'Start DfAnalyse container', 'module' => 'shell'],
                ['name' => 'Install Overseer dependencies', 'module' => 'apt'],
                ['name' => 'Prepare Overseer workspace', 'module' => 'file'],
                ['name' => 'Download Overseer source and resources', 'module' => 'shell'],
                ['name' => 'Start Overseer container', 'module' => 'shell'],
                ['name' => 'Install Server dependencies', 'module' => 'apt'],
                ['name' => 'Prepare server workspace', 'module' => 'file'],
                ['name' => 'Download Server source and resources', 'module' => 'shell'],
                ['name' => 'Start Server container', 'module' => 'shell'],
                ['name' => 'Install site dependencies', 'module' => 'apt'],
                ['name' => 'Prepare site workspace', 'module' => 'file'],
                ['name' => 'Download federated clustering source', 'module' => 'shell'],
                ['name' => 'Download site workspace package', 'module' => 'shell'],
                ['name' => 'Download dataset file for site', 'module' => 'shell'],
                ['name' => 'Prepare site dataset for NVFlare', 'module' => 'shell'],
                ['name' => 'Write hosts mapping for site', 'module' => 'shell'],
                ['name' => 'Start site container', 'module' => 'shell'],
                ['name' => 'Wait for DfAnalyse availability', 'module' => 'wait_for'],
                ['name' => 'Adjust server round count', 'module' => 'shell'],
                ['name' => 'Prepare job config', 'module' => 'shell'],
                ['name' => 'Submit NVFlare job', 'module' => 'shell'],
                ['name' => 'Write post-execution ansible_outputs.json', 'module' => 'copy'],
            ],
        ];
    }

    private function akoflowMulticloudBootstrap(): array
    {
        return [
            'template_slug'    => 'akoflow-multicloud',
            'template_version' => '1.0.0',
          'provider_type'    => 'custom',
            'name'             => 'AkôFlow Multicloud Bootstrap',
            'description'      => 'Installs Kind, provisions AkôFlow resources, generates the cluster token and starts the workflow engine container on the remote host.',
            'trigger'          => AnsiblePlaybook::TRIGGER_AFTER_PROVISION,
            'position'         => 0,
            'playbook_slug'    => 'akoflow-multicloud-bootstrap',
            'playbook_yaml'    => <<<'YAML'
- name: Bootstrap AkôFlow Multicloud server
  hosts: all
  become: true
  gather_facts: false

  pre_tasks:
        delay: 10
        sleep: 10
        timeout: 600

    - name: Gather facts
      setup:

  tasks:
    - name: Install AkôFlow bootstrap dependencies
      apt:
        name:
          - ca-certificates
          - curl
          - unzip
          - gnupg
          - lsb-release
          - apt-transport-https
          - jq
        state: present
        update_cache: yes

    - name: Install Docker runtime
      shell: |
        set -eux
        install -m 0755 -d /etc/apt/keyrings
        curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
        chmod a+r /etc/apt/keyrings/docker.gpg
        echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo \"$VERSION_CODENAME\") stable" | tee /etc/apt/sources.list.d/docker.list
        apt-get update -y
        apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
        systemctl enable docker
        systemctl start docker
        usermod -aG docker ubuntu || true
      changed_when: false

    - name: Install kubectl, AWS CLI and GCP CLI
      shell: |
        set -eux
        curl -fsSL https://pkgs.k8s.io/core:/stable:/v1.31/deb/Release.key | gpg --dearmor -o /etc/apt/keyrings/kubernetes-apt-keyring.gpg
        echo 'deb [signed-by=/etc/apt/keyrings/kubernetes-apt-keyring.gpg] https://pkgs.k8s.io/core:/stable:/v1.31/deb/ /' | tee /etc/apt/sources.list.d/kubernetes.list
        apt-get update -y
        apt-get install -y kubectl
        curl -fsSL "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o /tmp/awscliv2.zip
        cd /tmp && unzip -q awscliv2.zip && ./aws/install && rm -rf awscliv2.zip aws
        cd /
        echo "deb [signed-by=/usr/share/keyrings/cloud.google.gpg] https://packages.cloud.google.com/apt cloud-sdk main" | tee /etc/apt/sources.list.d/google-cloud-sdk.list
        curl -fsSL https://packages.cloud.google.com/apt/doc/apt-key.gpg | gpg --dearmor -o /usr/share/keyrings/cloud.google.gpg
        apt-get update -y
        apt-get install -y google-cloud-cli google-cloud-cli-gke-gcloud-auth-plugin
      changed_when: false

    - name: Prepare AkôFlow workspace
      shell: |
        set -eux
        mkdir -p /root/akospace /home/ubuntu/.kube
        chown -R ubuntu:ubuntu /root/akospace /home/ubuntu/.kube
      changed_when: false

    - name: Write GCP service account key
      copy:
        dest: /root/akospace/gcp-sa.json
        content: |
          {{ gcp_sa_key_json }}
        mode: '0600'

    - name: Configure EKS and GKE access
      shell: |
        set -eux
        resource_prefix="akocloud-{{ environment_id | default('') }}"
        if [ -z "{{ environment_id | default('') }}" ]; then
          resource_prefix="akocloud"
        fi
        eks_cluster_name="${resource_prefix}-eks"
        gke_cluster_name="${resource_prefix}-gke"

        export GOOGLE_APPLICATION_CREDENTIALS=/root/akospace/gcp-sa.json
        export USE_GKE_GCLOUD_AUTH_PLUGIN=True
        export CLOUDSDK_CORE_DISABLE_PROMPTS=1

        aws eks update-kubeconfig --name "${eks_cluster_name}" --region "{{ aws_region }}" --kubeconfig /home/ubuntu/.kube/config-eks

        gcloud auth activate-service-account --key-file=$GOOGLE_APPLICATION_CREDENTIALS
        gcloud config set project "{{ gcp_project_id }}"
        export KUBECONFIG=/home/ubuntu/.kube/config-gke
        gcloud container clusters get-credentials "${gke_cluster_name}" --region "{{ gcp_region }}" --project "{{ gcp_project_id }}"
        chown -R ubuntu:ubuntu /home/ubuntu/.kube
      changed_when: false

    - name: Apply AkôFlow manifests to both clusters
      shell: |
        set -eux
        AKOFLOW_YAML="https://raw.githubusercontent.com/UFFeScience/akoflow-workflow-engine/main/pkg/server/resource/akoflow-dev-dockerdesktop.yaml"

        for i in 1 2 3 4 5; do
          KUBECONFIG=/home/ubuntu/.kube/config-eks kubectl apply -f "$AKOFLOW_YAML" && break
          echo "  retry $i/5 in 30s..."
          sleep 30
        done

        for i in 1 2 3 4 5; do
          KUBECONFIG=/home/ubuntu/.kube/config-gke kubectl apply -f "$AKOFLOW_YAML" && break
          echo "  retry $i/5 in 30s..."
          sleep 30
        done

        for i in $(seq 1 30); do
          KUBECONFIG=/home/ubuntu/.kube/config-eks kubectl get serviceaccount akoflow-server-sa -n akoflow 2>/dev/null && break
          echo "  waiting... ($i/30)"
          sleep 10
        done

        for i in $(seq 1 30); do
          KUBECONFIG=/home/ubuntu/.kube/config-gke USE_GKE_GCLOUD_AUTH_PLUGIN=True kubectl get serviceaccount akoflow-server-sa -n akoflow 2>/dev/null && break
          echo "  waiting... ($i/30)"
          sleep 10
        done

        EKS_TOKEN=$(KUBECONFIG=/home/ubuntu/.kube/config-eks kubectl create token akoflow-server-sa --duration=800h --namespace=akoflow)
        GKE_TOKEN=$(KUBECONFIG=/home/ubuntu/.kube/config-gke USE_GKE_GCLOUD_AUTH_PLUGIN=True kubectl create token akoflow-server-sa --duration=800h --namespace=akoflow)
        EKS_API=$(KUBECONFIG=/home/ubuntu/.kube/config-eks kubectl config view --minify -o jsonpath='{.clusters[0].cluster.server}' | sed 's|https\?://||')
        GKE_API=$(KUBECONFIG=/home/ubuntu/.kube/config-gke kubectl config view --minify -o jsonpath='{.clusters[0].cluster.server}' | sed 's|https\?://||')
        INSTANCE_IP=$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4)

        cat > /root/akospace/.env << ENVEOF
        K8S1_API_SERVER_HOST=$EKS_API
        K8S1_API_SERVER_TOKEN=$EKS_TOKEN
        K8S2_API_SERVER_HOST=$GKE_API
        K8S2_API_SERVER_TOKEN=$GKE_TOKEN
        AKOFLOW_SERVER_SERVICE_SERVICE_HOST=$INSTANCE_IP
        AKOFLOW_SERVER_SERVICE_SERVICE_PORT=8080
        ENVEOF

        chown ubuntu:ubuntu /root/akospace/.env
        mkdir -p /akospace
        cp /root/akospace/.env /akospace/.env
      changed_when: false

    - name: Start AkôFlow engine
      shell: |
        set -eux
        cd /root/akospace
        curl -fsSL https://akoflow.com/run | bash
      changed_when: false

    - name: Write ansible_outputs.json
      copy:
        dest: "{{ playbook_dir }}/ansible_outputs.json"
        content: |
          {
            "akoflow_url": "http://{{ ansible_host }}:8080",
            "container_name": "akoflow"
          }
      delegate_to: localhost
YAML,
            'vars_mapping_json'    => [
                'environment_configuration' => [
                    'aws_region'       => 'aws_region',
                    'gcp_project_id'   => 'gcp_project_id',
                    'gcp_region'       => 'gcp_region',
                    'gcp_sa_key_json'  => 'gcp_sa_key_json',
                ],
            ],
            'outputs_mapping_json' => [
                'resources' => [[
                    'name'                  => 'akoflow-server',
                    'ansible_resource_type' => 'akoflow_engine',
                    'outputs'               => ['metadata' => ['akoflow_url' => 'akoflow_url']],
                ]],
            ],
            'credential_env_keys' => [],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Wait for SSH to become ready', 'module' => 'wait_for_connection'],
                ['name' => 'Gather facts', 'module' => 'setup'],
                ['name' => 'Install AkôFlow bootstrap dependencies', 'module' => 'apt'],
                ['name' => 'Install Docker runtime', 'module' => 'shell'],
                ['name' => 'Install kubectl, AWS CLI and GCP CLI', 'module' => 'shell'],
                ['name' => 'Prepare AkôFlow workspace', 'module' => 'shell'],
                ['name' => 'Write GCP service account key', 'module' => 'copy'],
                ['name' => 'Configure EKS and GKE access', 'module' => 'shell'],
                ['name' => 'Apply AkôFlow manifests to both clusters', 'module' => 'shell'],
                ['name' => 'Start AkôFlow engine', 'module' => 'shell'],
                ['name' => 'Write ansible_outputs.json', 'module' => 'copy'],
            ],
        ];
    }

    private function akoflowMulticloudEngineStart(): array
    {
        return [
            'template_slug'    => 'akoflow-multicloud',
            'template_version' => '1.0.0',
          'provider_type'    => 'custom',
            'name'             => 'Start AkôFlow Engine',
            'description'      => 'Starts the AkôFlow engine on the multicloud server.',
            'trigger'          => AnsiblePlaybook::TRIGGER_MANUAL,
            'position'         => 1,
            'playbook_slug'    => 'akoflow-multicloud-engine-start',
            'playbook_yaml'    => <<<'YAML'
- name: Start AkôFlow engine
  hosts: all
  become: true
  tasks:
    - name: Start AkôFlow engine
      shell: |
        set -eux
        cd /root/akospace
        curl -fsSL https://akoflow.com/run | bash start
      changed_when: false

    - name: Write ansible_outputs.json
      copy:
        dest: "{{ playbook_dir }}/ansible_outputs.json"
        content: |
          {
            "akoflow_status": "running",
            "akoflow_url": "http://{{ ansible_host }}:8080"
          }
      delegate_to: localhost
YAML,
            'vars_mapping_json'    => [
                'environment_configuration' => [],
            ],
            'outputs_mapping_json' => [
                'resources' => [[
                    'name'                  => 'akoflow-server',
                    'ansible_resource_type' => 'akoflow_engine',
                    'outputs'               => ['metadata' => ['akoflow_status' => 'akoflow_status', 'akoflow_url' => 'akoflow_url']],
                ]],
            ],
            'credential_env_keys' => [],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Start AkôFlow engine', 'module' => 'shell'],
                ['name' => 'Write ansible_outputs.json', 'module' => 'copy'],
            ],
        ];
    }

    private function akoflowMulticloudEngineRestart(): array
    {
        return [
            'template_slug'    => 'akoflow-multicloud',
            'template_version' => '1.0.0',
          'provider_type'    => 'custom',
            'name'             => 'Restart AkôFlow Engine',
            'description'      => 'Restarts the AkôFlow engine on the multicloud server.',
            'trigger'          => AnsiblePlaybook::TRIGGER_MANUAL,
            'position'         => 2,
            'playbook_slug'    => 'akoflow-multicloud-engine-restart',
            'playbook_yaml'    => <<<'YAML'
- name: Restart AkôFlow engine
  hosts: all
  become: true
  tasks:
    - name: Restart AkôFlow engine
      shell: |
        set -eux
        cd /root/akospace
        curl -fsSL https://akoflow.com/run | bash restart
      changed_when: false

    - name: Write ansible_outputs.json
      copy:
        dest: "{{ playbook_dir }}/ansible_outputs.json"
        content: |
          {
            "akoflow_status": "running",
            "akoflow_url": "http://{{ ansible_host }}:8080"
          }
      delegate_to: localhost
YAML,
            'vars_mapping_json'    => [
                'environment_configuration' => [],
            ],
            'outputs_mapping_json' => [
                'resources' => [[
                    'name'                  => 'akoflow-server',
                    'ansible_resource_type' => 'akoflow_engine',
                    'outputs'               => ['metadata' => ['akoflow_status' => 'akoflow_status', 'akoflow_url' => 'akoflow_url']],
                ]],
            ],
            'credential_env_keys' => [],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Restart AkôFlow engine', 'module' => 'shell'],
                ['name' => 'Write ansible_outputs.json', 'module' => 'copy'],
            ],
        ];
    }

    private function akoflowMulticloudEngineStop(): array
    {
        return [
            'template_slug'    => 'akoflow-multicloud',
            'template_version' => '1.0.0',
          'provider_type'    => 'custom',
            'name'             => 'Stop AkôFlow Engine',
            'description'      => 'Stops the AkôFlow engine on the multicloud server.',
            'trigger'          => AnsiblePlaybook::TRIGGER_MANUAL,
            'position'         => 3,
            'playbook_slug'    => 'akoflow-multicloud-engine-stop',
            'playbook_yaml'    => <<<'YAML'
- name: Stop AkôFlow engine
  hosts: all
  become: true
  tasks:
    - name: Stop AkôFlow engine
      shell: |
        set -eux
        cd /root/akospace
        curl -fsSL https://akoflow.com/run | bash stop
      changed_when: false

    - name: Write ansible_outputs.json
      copy:
        dest: "{{ playbook_dir }}/ansible_outputs.json"
        content: |
          {
            "akoflow_status": "stopped"
          }
      delegate_to: localhost
YAML,
            'vars_mapping_json'    => [
                'environment_configuration' => [],
            ],
            'outputs_mapping_json' => [
                'resources' => [[
                    'name'                  => 'akoflow-server',
                    'ansible_resource_type' => 'akoflow_engine',
                    'outputs'               => ['metadata' => ['akoflow_status' => 'akoflow_status']],
                ]],
            ],
            'credential_env_keys' => [],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Stop AkôFlow engine', 'module' => 'shell'],
                ['name' => 'Write ansible_outputs.json', 'module' => 'copy'],
            ],
        ];
    }

    // ─── AkôFlow Local Installer ─────────────────────────────────────────────

    private function localAkoflowInstall(): array
    {
        return [
            'template_slug'    => 'akoflow-local-installer',
            'template_version' => '1.0.0',
            'provider_type'    => 'local',
            'name'             => 'Install AkôFlow Workflow Engine',
            'description'      => 'Installs Kind, provisions AkôFlow resources, generates the cluster token and starts the workflow engine container on the remote host.',
            'trigger'          => AnsiblePlaybook::TRIGGER_AFTER_PROVISION,
            'position'         => 0,
            'playbook_yaml'    => <<<'YAML'
- name: Install AkôFlow workflow engine with Kind
  hosts: all
  become: false

  tasks:
    - name: Check Docker is installed
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        if [ -z "$DOCKER_BIN" ]; then
          echo 'Docker is not installed on this host'
          exit 1
        fi
        "$DOCKER_BIN" --version
      register: docker_check
      changed_when: false
      failed_when: docker_check.rc != 0

    - name: Check Kind is installed
      shell: |
        set -eux
        KIND_BIN="$(command -v kind || ls /usr/local/bin/kind /usr/bin/kind /opt/homebrew/bin/kind 2>/dev/null | head -1 || true)"
        if [ -z "$KIND_BIN" ]; then
          KIND_VERSION="v0.27.0"
          ARCH="$(uname -m)"
          case "$ARCH" in
            x86_64|amd64) KIND_ARCH="amd64" ;;
            arm64|aarch64) KIND_ARCH="arm64" ;;
            *)
              echo "Unsupported architecture for Kind: $ARCH"
              exit 1
              ;;
          esac
          OS="$(uname -s | tr '[:upper:]' '[:lower:]')"
          TMP_DIR="$(mktemp -d)"
          curl -fsSL -o "$TMP_DIR/kind" "https://kind.sigs.k8s.io/dl/${KIND_VERSION}/kind-${OS}-${KIND_ARCH}"
          chmod +x "$TMP_DIR/kind"
          install -m 0755 "$TMP_DIR/kind" /usr/local/bin/kind
          KIND_BIN=/usr/local/bin/kind
        fi
        "$KIND_BIN" version
      register: kind_check
      changed_when: false
      failed_when: kind_check.rc != 0

    - name: Check kubectl is installed
      shell: |
        set -eux
        KUBECTL_BIN="$(command -v kubectl || ls /usr/local/bin/kubectl /usr/bin/kubectl /opt/homebrew/bin/kubectl 2>/dev/null | head -1 || true)"
        if [ -z "$KUBECTL_BIN" ]; then
          KUBECTL_VERSION="$(curl -fsSL https://dl.k8s.io/release/stable.txt)"
          ARCH="$(uname -m)"
          case "$ARCH" in
            x86_64|amd64) KUBECTL_ARCH="amd64" ;;
            arm64|aarch64) KUBECTL_ARCH="arm64" ;;
            *)
              echo "Unsupported architecture for kubectl: $ARCH"
              exit 1
              ;;
          esac
          OS="$(uname -s | tr '[:upper:]' '[:lower:]')"
          TMP_DIR="$(mktemp -d)"
          curl -fsSL -o "$TMP_DIR/kubectl" "https://dl.k8s.io/release/${KUBECTL_VERSION}/bin/${OS}/${KUBECTL_ARCH}/kubectl"
          chmod +x "$TMP_DIR/kubectl"
          install -m 0755 "$TMP_DIR/kubectl" /usr/local/bin/kubectl
          KUBECTL_BIN=/usr/local/bin/kubectl
        fi
        "$KUBECTL_BIN" version --client
      register: kubectl_check
      changed_when: false
      failed_when: kubectl_check.rc != 0

    - name: Prepare AkôFlow workspace directory
      shell: |
        set -eux
        WORKSPACE_DIR="{{ akoflow_workflow_engine_workspace_dir | default('/Users/<username>/akospace') }}"
        if [ "$WORKSPACE_DIR" = '/Users/<username>/akospace' ]; then
          WORKSPACE_DIR="${HOME}/akospace"
        fi
        mkdir -p "$WORKSPACE_DIR"
      changed_when: false

    - name: Create Kind cluster, install AkôFlow resources and generate .env
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        KIND_BIN="$(command -v kind || ls /usr/local/bin/kind /usr/bin/kind /opt/homebrew/bin/kind 2>/dev/null | head -1 || true)"
        KUBECTL_BIN="$(command -v kubectl || ls /usr/local/bin/kubectl /usr/bin/kubectl /opt/homebrew/bin/kubectl 2>/dev/null | head -1 || true)"

        if [ -z "$DOCKER_BIN" ] || [ -z "$KIND_BIN" ] || [ -z "$KUBECTL_BIN" ]; then
          echo 'Docker, Kind or kubectl is not installed on this host'
          exit 1
        fi

        export PATH="$(dirname "$DOCKER_BIN"):$PATH"

        WORKSPACE_DIR="{{ akoflow_workflow_engine_workspace_dir | default('/Users/<username>/akospace') }}"
        if [ "$WORKSPACE_DIR" = '/Users/<username>/akospace' ]; then
          WORKSPACE_DIR="${HOME}/akospace"
        fi
        ENV_FILE_PATH="$WORKSPACE_DIR/.env"

        CLUSTER_NAME="{{ akoflow_kind_cluster_name | default('akoflow-cluster') }}"

        if "$KIND_BIN" get clusters | grep -qx "$CLUSTER_NAME"; then
          KUBE_CONTEXT="kind-${CLUSTER_NAME}"
          if ! "$KUBECTL_BIN" --context "$KUBE_CONTEXT" version --request-timeout=10s >/dev/null 2>&1; then
            "$KIND_BIN" delete cluster --name "$CLUSTER_NAME"
            "$KIND_BIN" create cluster --name "$CLUSTER_NAME"
          fi
        else
          "$KIND_BIN" create cluster --name "$CLUSTER_NAME"
        fi

        KUBE_CONTEXT="kind-${CLUSTER_NAME}"
        CLUSTER_INFO="$("$KUBECTL_BIN" --context "$KUBE_CONTEXT" cluster-info)"
        API_SERVER_URL="$(printf '%s\n' "$CLUSTER_INFO" | awk '/Kubernetes control plane is running at/ {print $7; exit}')"

        if [ -z "$API_SERVER_URL" ]; then
          echo 'Could not determine the Kubernetes API server endpoint from kind cluster-info'
          exit 1
        fi

        API_SERVER_HOST_PORT="${API_SERVER_URL#https://}"
        API_SERVER_HOST_PORT="${API_SERVER_HOST_PORT#http://}"

        API_SERVER_HOST="host.docker.internal"
        API_SERVER_PORT="${API_SERVER_HOST_PORT##*:}"
        if [ -z "$API_SERVER_PORT" ] || [ "$API_SERVER_PORT" = "$API_SERVER_HOST_PORT" ]; then
          echo 'Could not parse the Kubernetes API server port from kind cluster-info'
          exit 1
        fi

        "$KUBECTL_BIN" --context "$KUBE_CONTEXT" apply -f https://raw.githubusercontent.com/UFFeScience/akoflow-workflow-engine/main/pkg/server/resource/akoflow-dev-dockerdesktop.yaml

        for i in $(seq 1 30); do
          if "$KUBECTL_BIN" --context "$KUBE_CONTEXT" get serviceaccount akoflow-server-sa -n akoflow >/dev/null 2>&1; then
            break
          fi
          echo "Waiting for akoflow-server-sa... ($i/30)"
          sleep 10
        done

        TOKEN="$("$KUBECTL_BIN" --context "$KUBE_CONTEXT" create token akoflow-server-sa --duration=800h --namespace=akoflow)"

        printf '%s\n' \
          "K8S_API_SERVER_HOST=${API_SERVER_HOST}:${API_SERVER_PORT}" \
          "K8S_API_SERVER_TOKEN=${TOKEN}" \
          "AKOFLOW_SERVER_SERVICE_SERVICE_HOST=host.docker.internal" \
          "AKOFLOW_SERVER_SERVICE_SERVICE_PORT=8080" \
          > "$ENV_FILE_PATH"
      changed_when: false

    - name: Remove existing AkôFlow workflow engine container if present
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        if [ -z "$DOCKER_BIN" ]; then
          echo 'Docker is not installed on this host'
          exit 1
        fi
        export PATH="$(dirname "$DOCKER_BIN"):$PATH"
        "$DOCKER_BIN" rm -f "{{ akoflow_workflow_engine_container_name }}" >/dev/null 2>&1 || true
      changed_when: false

    - name: Start AkôFlow workflow engine container
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        if [ -z "$DOCKER_BIN" ]; then
          echo 'Docker is not installed on this host'
          exit 1
        fi
        WORKSPACE_DIR="{{ akoflow_workflow_engine_workspace_dir | default('/Users/<username>/akospace') }}"
        if [ "$WORKSPACE_DIR" = '/Users/<username>/akospace' ]; then
          WORKSPACE_DIR="${HOME}/akospace"
        fi
        ENV_FILE_PATH="$WORKSPACE_DIR/.env"
        export PATH="$(dirname "$DOCKER_BIN"):$PATH"

        if ! "$DOCKER_BIN" image inspect akoflow/akoflow-workflow-engine:latest >/dev/null 2>&1; then
          TEMP_DOCKER_CONFIG="$(mktemp -d)"
          printf '{}' > "$TEMP_DOCKER_CONFIG/config.json"
          DOCKER_CONFIG="$TEMP_DOCKER_CONFIG" "$DOCKER_BIN" pull akoflow/akoflow-workflow-engine:latest
          rm -rf "$TEMP_DOCKER_CONFIG"
        fi

        "$DOCKER_BIN" run -d --rm \
          --name "{{ akoflow_workflow_engine_container_name }}" \
          -p "{{ akoflow_workflow_engine_host_port }}:8080" \
          --add-host host.docker.internal:host-gateway \
          -v "$ENV_FILE_PATH:/app/.env:ro" \
          akoflow/akoflow-workflow-engine:latest

    - name: Write ansible_outputs.json
      copy:
        dest: /tmp/ansible_outputs.json
        content: |
          {
            "akoflow_url": "http://localhost:{{ akoflow_workflow_engine_host_port }}"
          }
YAML,
            'vars_mapping_json'    => [
                'environment_configuration' => [
                    'ssh_user'                               => 'ansible_user',
                    'akoflow_workflow_engine_container_name' => 'akoflow_workflow_engine_container_name',
                    'akoflow_workflow_engine_host_port'      => 'akoflow_workflow_engine_host_port',
                'akoflow_workflow_engine_workspace_dir'  => 'akoflow_workflow_engine_workspace_dir',
                ],
            ],
            'outputs_mapping_json' => [
                'resources' => [[
                    'name'                  => 'akoflow-host',
                    'ansible_resource_type' => 'akoflow_install',
                    'outputs'               => ['metadata' => ['akoflow_url' => 'akoflow_url']],
                ]],
            ],
            'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Check Docker is installed', 'module' => 'shell'],
              ['name' => 'Check Kind is installed', 'module' => 'shell'],
              ['name' => 'Check kubectl is installed', 'module' => 'shell'],
              ['name' => 'Prepare AkôFlow workspace directory', 'module' => 'shell'],
              ['name' => 'Create Kind cluster, install AkôFlow resources and generate .env', 'module' => 'shell'],
                ['name' => 'Remove existing AkôFlow workflow engine container if present', 'module' => 'shell'],
                ['name' => 'Start AkôFlow workflow engine container', 'module' => 'shell'],
                ['name' => 'Write ansible_outputs.json', 'module' => 'copy'],
            ],
        ];
    }

    private function localAkoflowRestart(): array
    {
        return [
            'template_slug'    => 'akoflow-local-installer',
            'template_version' => '1.0.0',
            'provider_type'    => 'local',
            'name'             => 'Restart AkôFlow Workflow Engine',
            'description'      => 'Recreates the AkôFlow workflow engine container on the host using Docker.',
            'trigger'          => AnsiblePlaybook::TRIGGER_MANUAL,
            'position'         => 2,
            'playbook_yaml'    => <<<'YAML'
- name: Restart AkôFlow workflow engine container
  hosts: all
  become: false

  tasks:
    - name: Remove existing AkôFlow workflow engine container if present
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        if [ -z "$DOCKER_BIN" ]; then
          echo 'Docker is not installed on this host'
          exit 1
        fi
        export PATH="$(dirname "$DOCKER_BIN"):$PATH"
        "$DOCKER_BIN" rm -f "{{ akoflow_workflow_engine_container_name }}" >/dev/null 2>&1 || true

    - name: Delete existing Kind cluster if present
      shell: |
        set -eux
        KIND_BIN="$(command -v kind || ls /usr/local/bin/kind /usr/bin/kind /opt/homebrew/bin/kind 2>/dev/null | head -1 || true)"
        if [ -z "$KIND_BIN" ]; then
          echo 'Kind is not installed on this host'
          exit 1
        fi
        CLUSTER_NAME="{{ akoflow_kind_cluster_name | default('akoflow-cluster') }}"
        if "$KIND_BIN" get clusters | grep -qx "$CLUSTER_NAME"; then
          "$KIND_BIN" delete cluster --name "$CLUSTER_NAME"
        fi
      changed_when: false

    - name: Recreate Kind cluster, install AkôFlow resources and regenerate .env
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        KIND_BIN="$(command -v kind || ls /usr/local/bin/kind /usr/bin/kind /opt/homebrew/bin/kind 2>/dev/null | head -1 || true)"
        KUBECTL_BIN="$(command -v kubectl || ls /usr/local/bin/kubectl /usr/bin/kubectl /opt/homebrew/bin/kubectl 2>/dev/null | head -1 || true)"

        if [ -z "$DOCKER_BIN" ] || [ -z "$KIND_BIN" ] || [ -z "$KUBECTL_BIN" ]; then
          echo 'Docker, Kind or kubectl is not installed on this host'
          exit 1
        fi

        export PATH="$(dirname "$DOCKER_BIN"):$PATH"

        WORKSPACE_DIR="{{ akoflow_workflow_engine_workspace_dir | default('/Users/<username>/akospace') }}"
        if [ "$WORKSPACE_DIR" = '/Users/<username>/akospace' ]; then
          WORKSPACE_DIR="${HOME}/akospace"
        fi
        mkdir -p "$WORKSPACE_DIR"
        ENV_FILE_PATH="$WORKSPACE_DIR/.env"

        CLUSTER_NAME="{{ akoflow_kind_cluster_name | default('akoflow-cluster') }}"
        "$KIND_BIN" create cluster --name "$CLUSTER_NAME"

        KUBE_CONTEXT="kind-${CLUSTER_NAME}"
        CLUSTER_INFO="$("$KUBECTL_BIN" --context "$KUBE_CONTEXT" cluster-info)"
        API_SERVER_URL="$(printf '%s\n' "$CLUSTER_INFO" | awk '/Kubernetes control plane is running at/ {print $7; exit}')"

        if [ -z "$API_SERVER_URL" ]; then
          echo 'Could not determine the Kubernetes API server endpoint from kind cluster-info'
          exit 1
        fi

        API_SERVER_HOST_PORT="${API_SERVER_URL#https://}"
        API_SERVER_HOST_PORT="${API_SERVER_HOST_PORT#http://}"

        API_SERVER_HOST="host.docker.internal"
        API_SERVER_PORT="${API_SERVER_HOST_PORT##*:}"
        if [ -z "$API_SERVER_PORT" ] || [ "$API_SERVER_PORT" = "$API_SERVER_HOST_PORT" ]; then
          echo 'Could not parse the Kubernetes API server port from kind cluster-info'
          exit 1
        fi

        "$KUBECTL_BIN" --context "$KUBE_CONTEXT" apply -f https://raw.githubusercontent.com/UFFeScience/akoflow-workflow-engine/main/pkg/server/resource/akoflow-dev-dockerdesktop.yaml

        for i in $(seq 1 30); do
          if "$KUBECTL_BIN" --context "$KUBE_CONTEXT" get serviceaccount akoflow-server-sa -n akoflow >/dev/null 2>&1; then
            break
          fi
          echo "Waiting for akoflow-server-sa... ($i/30)"
          sleep 10
        done

        TOKEN="$("$KUBECTL_BIN" --context "$KUBE_CONTEXT" create token akoflow-server-sa --duration=800h --namespace=akoflow)"

        printf '%s\n' \
          "K8S_API_SERVER_HOST=${API_SERVER_HOST}:${API_SERVER_PORT}" \
          "K8S_API_SERVER_TOKEN=${TOKEN}" \
          "AKOFLOW_SERVER_SERVICE_SERVICE_HOST=host.docker.internal" \
          "AKOFLOW_SERVER_SERVICE_SERVICE_PORT=8080" \
          > "$ENV_FILE_PATH"
      changed_when: false

    - name: Restart AkôFlow workflow engine container
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        if [ -z "$DOCKER_BIN" ]; then
          echo 'Docker is not installed on this host'
          exit 1
        fi
        export PATH="$(dirname "$DOCKER_BIN"):$PATH"
        WORKSPACE_DIR="{{ akoflow_workflow_engine_workspace_dir | default('/Users/<username>/akospace') }}"
        if [ "$WORKSPACE_DIR" = '/Users/<username>/akospace' ]; then
          WORKSPACE_DIR="${HOME}/akospace"
        fi
        ENV_FILE_PATH="$WORKSPACE_DIR/.env"

        if ! "$DOCKER_BIN" image inspect akoflow/akoflow-workflow-engine:latest >/dev/null 2>&1; then
          TEMP_DOCKER_CONFIG="$(mktemp -d)"
          printf '{}' > "$TEMP_DOCKER_CONFIG/config.json"
          DOCKER_CONFIG="$TEMP_DOCKER_CONFIG" "$DOCKER_BIN" pull akoflow/akoflow-workflow-engine:latest
          rm -rf "$TEMP_DOCKER_CONFIG"
        fi

        "$DOCKER_BIN" run -d --rm \
          --name "{{ akoflow_workflow_engine_container_name }}" \
          -p "{{ akoflow_workflow_engine_host_port }}:8080" \
          --add-host host.docker.internal:host-gateway \
          -v "$ENV_FILE_PATH:/app/.env:ro" \
          akoflow/akoflow-workflow-engine:latest
YAML,
            'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user', 'akoflow_workflow_engine_container_name' => 'akoflow_workflow_engine_container_name', 'akoflow_workflow_engine_host_port' => 'akoflow_workflow_engine_host_port', 'akoflow_workflow_engine_workspace_dir' => 'akoflow_workflow_engine_workspace_dir', 'akoflow_kind_cluster_name' => 'akoflow_kind_cluster_name']],
            'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Remove existing AkôFlow workflow engine container if present', 'module' => 'shell'],
                ['name' => 'Delete existing Kind cluster if present', 'module' => 'shell'],
                ['name' => 'Recreate Kind cluster, install AkôFlow resources and regenerate .env', 'module' => 'shell'],
                ['name' => 'Restart AkôFlow workflow engine container', 'module' => 'shell'],
            ],
        ];
    }

    private function localAkoflowStart(): array
    {
        return [
            'template_slug'    => 'akoflow-local-installer',
            'template_version' => '1.0.0',
            'provider_type'    => 'local',
            'name'             => 'Start AkôFlow Workflow Engine',
            'description'      => 'Starts the AkôFlow workflow engine container on the host using Docker.',
            'trigger'          => AnsiblePlaybook::TRIGGER_MANUAL,
            'position'         => 1,
            'playbook_yaml'    => <<<'YAML'
- name: Start AkôFlow workflow engine container
  hosts: all
  become: false

  tasks:
    - name: Start AkôFlow workflow engine container
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        if [ -z "$DOCKER_BIN" ]; then
          echo 'Docker is not installed on this host'
          exit 1
        fi
        export PATH="$(dirname "$DOCKER_BIN"):$PATH"
        WORKSPACE_DIR="{{ akoflow_workflow_engine_workspace_dir | default('/Users/<username>/akospace') }}"
        if [ "$WORKSPACE_DIR" = '/Users/<username>/akospace' ]; then
          WORKSPACE_DIR="${HOME}/akospace"
        fi
        ENV_FILE_PATH="$WORKSPACE_DIR/.env"

        if ! "$DOCKER_BIN" image inspect akoflow/akoflow-workflow-engine:latest >/dev/null 2>&1; then
          TEMP_DOCKER_CONFIG="$(mktemp -d)"
          printf '{}' > "$TEMP_DOCKER_CONFIG/config.json"
          DOCKER_CONFIG="$TEMP_DOCKER_CONFIG" "$DOCKER_BIN" pull akoflow/akoflow-workflow-engine:latest
          rm -rf "$TEMP_DOCKER_CONFIG"
        fi

        "$DOCKER_BIN" run -d --rm \
          --name "{{ akoflow_workflow_engine_container_name }}" \
          -p "{{ akoflow_workflow_engine_host_port }}:8080" \
          --add-host host.docker.internal:host-gateway \
          -v "$ENV_FILE_PATH:/app/.env:ro" \
          akoflow/akoflow-workflow-engine:latest

    - name: Write ansible_outputs.json
      copy:
        dest: /tmp/ansible_outputs.json
        content: |
          {
            "akoflow_status": "running",
            "akoflow_url": "http://localhost:{{ akoflow_workflow_engine_host_port }}"
          }
YAML,
            'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user', 'akoflow_workflow_engine_container_name' => 'akoflow_workflow_engine_container_name', 'akoflow_workflow_engine_host_port' => 'akoflow_workflow_engine_host_port', 'akoflow_workflow_engine_workspace_dir' => 'akoflow_workflow_engine_workspace_dir']],
            'outputs_mapping_json' => [
                'resources' => [[
                    'name'                  => 'akoflow-host',
                    'ansible_resource_type' => 'akoflow_install',
                    'outputs'               => ['metadata' => ['akoflow_status' => 'akoflow_status', 'akoflow_url' => 'akoflow_url']],
                ]],
            ],
            'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Start AkôFlow workflow engine container', 'module' => 'shell'],
                ['name' => 'Write ansible_outputs.json', 'module' => 'copy'],
            ],
        ];
    }

    private function localAkoflowStop(): array
    {
        return [
            'template_slug'    => 'akoflow-local-installer',
            'template_version' => '1.0.0',
            'provider_type'    => 'local',
            'name'             => 'Stop AkôFlow Workflow Engine',
            'description'      => 'Stops the AkôFlow workflow engine container on the host.',
            'trigger'          => AnsiblePlaybook::TRIGGER_MANUAL,
            'position'         => 3,
            'playbook_yaml'    => <<<'YAML'
- name: Stop AkôFlow workflow engine container
  hosts: all
  become: false

  tasks:
    - name: Stop AkôFlow workflow engine container
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        if [ -z "$DOCKER_BIN" ]; then
          echo 'Docker is not installed on this host'
          exit 1
        fi
        export PATH="$(dirname "$DOCKER_BIN"):$PATH"
        "$DOCKER_BIN" rm -f "{{ akoflow_workflow_engine_container_name }}" >/dev/null 2>&1 || true

    - name: Delete Kind cluster created for AkôFlow if present
      shell: |
        set -eux
        KIND_BIN="$(command -v kind || ls /usr/local/bin/kind /usr/bin/kind /opt/homebrew/bin/kind 2>/dev/null | head -1 || true)"
        if [ -z "$KIND_BIN" ]; then
          echo 'Kind is not installed on this host'
          exit 1
        fi
        CLUSTER_NAME="{{ akoflow_kind_cluster_name | default('akoflow-cluster') }}"
        "$KIND_BIN" delete cluster --name "$CLUSTER_NAME" >/dev/null 2>&1 || true
      changed_when: false

    - name: Write ansible_outputs.json
      copy:
        dest: /tmp/ansible_outputs.json
        content: |
          {
            "akoflow_status": "stopped"
          }
YAML,
            'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user', 'akoflow_workflow_engine_container_name' => 'akoflow_workflow_engine_container_name', 'akoflow_kind_cluster_name' => 'akoflow_kind_cluster_name']],
            'outputs_mapping_json' => [
                'resources' => [[
                    'name'                  => 'akoflow-host',
                    'ansible_resource_type' => 'akoflow_install',
                    'outputs'               => ['metadata' => ['akoflow_status' => 'akoflow_status']],
                ]],
            ],
            'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Stop AkôFlow workflow engine container', 'module' => 'shell'],
              ['name' => 'Delete Kind cluster created for AkôFlow if present', 'module' => 'shell'],
                ['name' => 'Write ansible_outputs.json', 'module' => 'copy'],
            ],
        ];
    }

    private function localAkoflowTeardown(): array
    {
        return [
            'template_slug'    => 'akoflow-local-installer',
            'template_version' => '1.0.0',
            'provider_type'    => 'local',
            'name'             => 'Remove AkôFlow Workflow Engine',
            'description'      => 'Removes the AkôFlow workflow engine container, Kind cluster, workspace assets and local image before teardown.',
            'trigger'          => AnsiblePlaybook::TRIGGER_BEFORE_TEARDOWN,
            'position'         => 4,
            'playbook_yaml'    => <<<'YAML'
- name: Remove AkôFlow workflow engine container
  hosts: all
  become: false

  tasks:
    - name: Remove AkôFlow workflow engine container
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        if [ -z "$DOCKER_BIN" ]; then
          echo 'Docker is not installed on this host'
          exit 1
        fi
        export PATH="$(dirname "$DOCKER_BIN"):$PATH"
        "$DOCKER_BIN" rm -f "{{ akoflow_workflow_engine_container_name }}" >/dev/null 2>&1 || true

    - name: Delete Kind cluster created for AkôFlow if present
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        KIND_BIN="$(command -v kind || ls /usr/local/bin/kind /usr/bin/kind /opt/homebrew/bin/kind 2>/dev/null | head -1 || true)"
        CLUSTER_NAME="{{ akoflow_kind_cluster_name | default('akoflow-cluster') }}"
        CONTROL_PLANE_CONTAINER="${CLUSTER_NAME}-control-plane"

        if [ -n "$KIND_BIN" ]; then
          "$KIND_BIN" delete cluster --name "$CLUSTER_NAME" >/dev/null 2>&1 || true
        fi

        if [ -n "$DOCKER_BIN" ]; then
          export PATH="$(dirname "$DOCKER_BIN"):$PATH"
          "$DOCKER_BIN" rm -f "$CONTROL_PLANE_CONTAINER" >/dev/null 2>&1 || true
        fi

        if [ -n "$KIND_BIN" ] && "$KIND_BIN" get clusters | grep -qx "$CLUSTER_NAME"; then
          echo "Failed to remove Kind cluster: $CLUSTER_NAME"
          exit 1
        fi

        if [ -n "$DOCKER_BIN" ] && "$DOCKER_BIN" ps -a --format '{{.Names}}' | grep -qx "$CONTROL_PLANE_CONTAINER"; then
          echo "Failed to remove Kind control-plane container: $CONTROL_PLANE_CONTAINER"
          exit 1
        fi
      changed_when: false

    - name: Remove AkôFlow workspace directory
      shell: |
        set -eux
        WORKSPACE_DIR="{{ akoflow_workflow_engine_workspace_dir | default('/Users/<username>/akospace') }}"
        if [ "$WORKSPACE_DIR" = '/Users/<username>/akospace' ]; then
          WORKSPACE_DIR="${HOME}/akospace"
        fi
        rm -rf "$WORKSPACE_DIR"
      changed_when: false

    - name: Remove AkôFlow workflow engine image if present
      shell: |
        set -eux
        DOCKER_BIN="$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker /opt/homebrew/bin/docker 2>/dev/null | head -1 || true)"
        if [ -z "$DOCKER_BIN" ]; then
          echo 'Docker is not installed on this host'
          exit 1
        fi
        export PATH="$(dirname "$DOCKER_BIN"):$PATH"
        "$DOCKER_BIN" image rm -f akoflow/akoflow-workflow-engine:latest >/dev/null 2>&1 || true
      changed_when: false
YAML,
            'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user', 'akoflow_workflow_engine_container_name' => 'akoflow_workflow_engine_container_name', 'akoflow_workflow_engine_workspace_dir' => 'akoflow_workflow_engine_workspace_dir', 'akoflow_kind_cluster_name' => 'akoflow_kind_cluster_name']],
            'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Remove AkôFlow workflow engine container', 'module' => 'shell'],
                ['name' => 'Delete Kind cluster created for AkôFlow if present', 'module' => 'shell'],
                ['name' => 'Remove AkôFlow workspace directory', 'module' => 'shell'],
                ['name' => 'Remove AkôFlow workflow engine image if present', 'module' => 'shell'],
            ],
        ];
    }
}
