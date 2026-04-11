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

            // ── SSCAD 2025 Federated Learning ──────────────────────────────
            $this->sscad2025Bootstrap(),

            // ── AkôFlow Local Installer ────────────────────────────────────
            $this->localAkoflowInstall(),
            $this->localAkoflowRestart(),
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

    // ─── SSCAD 2025 ──────────────────────────────────────────────────────────

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
            'template_slug'    => 'sscad-2025-fed-learning',
            'template_version' => '1.0.0',
            'provider_type'    => 'gcp',
            'name'             => 'SSCAD 2025 Bootstrap & Execution',
            'description'      => 'Bootstraps all nodes, starts the NVFlare federation (DfAnalyse, Overseer, Server, Sites) and submits the experiment job once the environment is ready.',
            'trigger'          => AnsiblePlaybook::TRIGGER_AFTER_PROVISION,
            'position'         => 0,
            'playbook_slug'    => 'sscad-gcp-bootstrap',
            'inventory_template' => null,
            'playbook_yaml'    => <<<'YAML'
- name: SSCAD 2025 bootstrap
  hosts: all
  become: true

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

- name: SSCAD 2025 startup DfAnalyse
  hosts: dfanalyse
  become: true

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
        mv federated_clustering-main/dfanalyzer/ /DfAnalyzer/DfAnalyzer
        rm -rf main.zip federated_clustering-main
      changed_when: false

    - name: Start DfAnalyse container
      shell: |
        set -eux
        cd /DfAnalyzer/DfAnalyzer
        docker run --rm --name dfanalyzer -d -p 22000:22000 -e DFA_URL=http://0.0.0.0 -w /DfAnalyzer -v $(pwd)/save_results.sql:/DfAnalyzer/save_results.sql -v $(pwd)/data:/DfAnalyzer/data -v $(pwd)/results:/DfAnalyzer/results nymeria0042/dfanalyzer sh start-dfanalyzer.sh
      changed_when: false

- name: SSCAD 2025 startup Overseer
  hosts: overseer
  become: true

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
        mv federated_clustering-main/fed-clustering/ /fed-clustering/app
        rm -rf main.zip federated_clustering-main
        wget https://storage.googleapis.com/outliers-ccpe-2026/infra-sscad-2/prod_01.zip -O prod_01.zip
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
        docker run --rm --name overseer --add-host overseer:$(hostname -I | awk '{print $1}') -p 8443:8443 -v $(pwd)/overseer:/workspace -e WORKSPACE=/workspace -e IMAGE_NAME=ovvesley/nvflare-service:latest ovvesley/nvflare-service:latest /workspace/startup/start.sh
      changed_when: false

- name: SSCAD 2025 startup Server
  hosts: server
  become: true
  vars:
    dfa_host: "{{ hostvars['dfanalyse'].ansible_host }}"
    overseer_host: "{{ hostvars['overseer'].ansible_host }}"

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
        mv federated_clustering-main/fed-clustering/ /fed-clustering/app
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

    - name: Start Server container
      shell: |
        set -eux
        echo "{{ overseer_host }} overseer" >> /etc/hosts
        docker run --rm --name server1 -v $(pwd)/server1:/workspace -v nvflare_svc_persist:/tmp/nvflare/ --add-host overseer:{{ overseer_host }} --add-host server1:$(hostname -I | awk '{print $1}') -e DFA_URL=http://{{ dfa_host }}:22000 -p 8002:8002 -p 8003:8003 -e PYTHON_EXECUTABLE=python3 -e WORKSPACE=/workspace -e IMAGE_NAME=ovvesley/nvflare-service:latest -w /workspace/server1 ovvesley/nvflare-service:latest python -u -m nvflare.private.fed.app.server.server_train -m /workspace -s fed_server.json --set secure_train=true config_folder=config org=nvidia
      changed_when: false

- name: SSCAD 2025 startup Sites
  hosts: all
  become: true
  vars:
    server_host: "{{ hostvars['server'].ansible_host }}"
    overseer_host: "{{ hostvars['overseer'].ansible_host }}"
    dfa_host: "{{ hostvars['dfanalyse'].ansible_host }}"

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
        mv federated_clustering-main/fed-clustering/ /fed-clustering/app
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
        site_name={{ inventory_hostname }}
        docker run --rm --name "$site_name" --add-host overseer:{{ overseer_host }} --add-host server1:{{ server_host }} -e DFA_URL=http://{{ dfa_host }}:22000 -e OVERSEER_HOST=overseer -e SERVER_HOST=server1 -e SITE_NAME=$site_name -e DATASET_URL={{ dataset_folder_key }} -e SITE_FOLDER_URL={{ site_folder_url }} -v $(pwd)/$site_name:/workspace ovvesley/nvflare-service:latest /workspace/startup/start.sh
      changed_when: false
      when: inventory_hostname is match('^site-')

- name: SSCAD 2025 post execution on server
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
        sudo docker exec server1 bash -c '
          export PYTHON_EXECUTABLE=python3
          export WORKSPACE=/workspace
          export IMAGE_NAME=ovvesley/nvflare-service:latest
          sed -i -e "s/\"num_rounds\": *100/\"num_rounds\": 5/" /fed-clustering/jobs/sklearn_${ALGORITHM}_base/app/config/config_fed_server.json
        '
      environment:
        ALGORITHM: "{{ algorithm }}"
      changed_when: false

    - name: Prepare job config
      shell: |
        set -eux
        sudo docker exec server1 bash -c '
          export PYTHON_EXECUTABLE=python3
          export WORKSPACE=/workspace
          export IMAGE_NAME=ovvesley/nvflare-service:latest
          sed -i -e "s/NUM_CLIENTS=20/NUM_CLIENTS='"${CLIENTS}"'/" /fed-clustering/prepare_job_config.sh
          sed -i -e "s/sklearn_kmeans/sklearn_${ALGORITHM}/" /fed-clustering/prepare_job_config.sh
          cd /fed-clustering
          source prepare_job_config.sh
          cp -R /fed-clustering/jobs/* /workspace/admin/transfer/
        '
      environment:
        ALGORITHM: "{{ algorithm }}"
        CLIENTS: "{{ clients }}"
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

    // ─── AkôFlow Local Installer ─────────────────────────────────────────────

    private function localAkoflowInstall(): array
    {
        return [
            'template_slug'    => 'akoflow-local-installer',
            'template_version' => '1.0.0',
            'provider_type'    => 'local',
            'name'             => 'Install AkôFlow',
            'description'      => 'Installs and starts the AkôFlow container on the remote host via Docker.',
            'trigger'          => AnsiblePlaybook::TRIGGER_AFTER_PROVISION,
            'position'         => 0,
            'playbook_yaml'    => <<<'YAML'
- name: Install AkôFlow on remote host via Docker
  hosts: all
  become: false
  environment:
    PATH: "/usr/local/bin:/usr/bin:/bin:/opt/homebrew/bin:{{ ansible_env.HOME }}/.local/bin:{{ ansible_env.PATH | default('') }}"
  vars:
    akoflow_port: "{{ akoflow_port | default('8080') }}"
    akospace_dir: "{{ akospace_dir | default(ansible_env.HOME + '/akospace') }}"

  tasks:
    - name: Check Docker is installed
      shell: docker --version
      register: docker_check
      changed_when: false
      failed_when: docker_check.rc != 0

    - name: Create akospace directory
      file:
        path: "{{ akospace_dir }}"
        state: directory
        mode: '0755'

    - name: Create .env file
      copy:
        dest: "{{ akospace_dir }}/.env"
        content: |
          AKOFLOW_ENV=dev
          AKOFLOW_PORT={{ akoflow_port }}
        force: no

    - name: Run AkôFlow container
      shell: |
        docker run -d \
          --name akoflow-installer \
          --restart unless-stopped \
          -p {{ akoflow_port }}:8080 \
          -v "{{ akospace_dir | expanduser }}/.env:/app/.env" \
          -v "{{ akospace_dir | expanduser }}/ako.log:/app/ako.log" \
          -v "{{ akospace_dir | expanduser }}/database.db:/storage/database.db" \
          akoflow-installer

    - name: Write ansible_outputs.json
      copy:
        dest: /tmp/ansible_outputs.json
        content: |
          {
            "akoflow_url": "http://localhost:{{ akoflow_port }}"
          }
YAML,
            'vars_mapping_json'    => [
                'environment_configuration' => [
                    'ssh_user'     => 'ansible_user',
                    'akoflow_port' => 'akoflow_port',
                    'akospace_dir' => 'akospace_dir',
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
                ['name' => 'Create akospace directory', 'module' => 'file'],
                ['name' => 'Create .env file', 'module' => 'copy'],
                ['name' => 'Run AkôFlow container', 'module' => 'shell'],
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
            'name'             => 'Restart AkôFlow',
            'description'      => 'Restarts the akoflow-installer container on the host.',
            'trigger'          => AnsiblePlaybook::TRIGGER_MANUAL,
            'position'         => 0,
            'playbook_yaml'    => <<<'YAML'
- name: Restart AkôFlow container
  hosts: all
  become: false
  tasks:
    - name: Restart akoflow-installer container
      shell: docker restart akoflow-installer
YAML,
            'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user']],
            'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],
            'roles_json'          => [],
            'tasks'               => [
                ['name' => 'Restart akoflow-installer container', 'module' => 'shell'],
            ],
        ];
    }
}
