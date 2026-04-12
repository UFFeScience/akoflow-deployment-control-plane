<?php

namespace App\Services;

use App\Models\AnsiblePlaybookRun;
use App\Models\AnsiblePlaybookRunTaskHost;
use App\Models\AnsiblePlaybookTask;
use App\Repositories\RunLogRepository;

class AnsiblePlaybookTaskHostStatusService
{
    private const PENDING_HOST_PLACEHOLDER = '__pending_host__';

    /** @var array<int, string|null> */
    private array $currentTaskByRun = [];

    /** @var array<int, array<string, AnsiblePlaybookTask>> */
    private array $tasksByNameByRun = [];

    public function __construct(
        private RunLogRepository $runLogRepository,
    ) {}

    public function consumeLogLine(AnsiblePlaybookRun $run, string $line): void
    {
        $line = trim($line);
        if ($line === '') {
            return;
        }

        $runId = (int) $run->id;
        $tasksByName = $this->tasksByNameByRun[$runId] ?? $this->loadTasksByName($run);

        if (preg_match('/^TASK \[(.+?)\] \*+$/', $line, $m)) {
            $taskName = trim($m[1]);
            $this->currentTaskByRun[$runId] = $taskName;
            $this->markTaskRunning($run, $taskName, $tasksByName);
            return;
        }

        if (preg_match('/^([^\s|]+)\s*\|\s*UNREACHABLE!/i', $line, $m)) {
            $this->markHostPendingTasksAsUnreachable(
                $run,
                trim($m[1]),
                null,
                $line,
            );
            return;
        }

        if (preg_match('/^([^\s|]+)\s*\|\s*FAILED!/i', $line, $m)) {
            $this->markHostPendingTasksAsUnreachable(
                $run,
                trim($m[1]),
                null,
                $line,
            );
            return;
        }

        $currentTaskName = $this->currentTaskByRun[$runId] ?? null;
        if (! $currentTaskName) {
            return;
        }

        if (preg_match('/^(ok|changed|skipping): \[([^\]]+)\]/i', $line, $m)) {
            $host   = trim($m[2]);
            $status = match (strtolower($m[1])) {
                'ok'       => AnsiblePlaybookRunTaskHost::STATUS_OK,
                'changed'  => AnsiblePlaybookRunTaskHost::STATUS_CHANGED,
                'skipping' => AnsiblePlaybookRunTaskHost::STATUS_SKIPPED,
                default    => AnsiblePlaybookRunTaskHost::STATUS_OK,
            };

            $this->updateTaskHost($run, $currentTaskName, $host, $status, $line, $tasksByName);
            return;
        }

        if (preg_match('/^fatal: \[([^\]]+)\]: UNREACHABLE!/i', $line, $m)) {
            $this->updateTaskHost(
                $run,
                $currentTaskName,
                trim($m[1]),
                AnsiblePlaybookRunTaskHost::STATUS_UNREACHABLE,
                $line,
                $tasksByName,
            );
            return;
        }

        if (preg_match('/^fatal: \[([^\]]+)\]: FAILED!/i', $line, $m)) {
            $this->updateTaskHost(
                $run,
                $currentTaskName,
                trim($m[1]),
                AnsiblePlaybookRunTaskHost::STATUS_FAILED,
                $line,
                $tasksByName,
            );
        }
    }

    public function initializePending(AnsiblePlaybookRun $run): void
    {
        $run->loadMissing('activity.tasks');

        $tasks = $run->activity?->tasks ?? collect();
        if ($tasks->isEmpty()) {
            return;
        }

        $groupHosts = $this->parseInventoryGroupHosts($run->inventory_ini ?? null);
        $allHosts   = array_values(array_unique(array_merge(...array_values($groupHosts))));

        if (empty($allHosts)) {
            foreach ($tasks as $task) {
                /** @var AnsiblePlaybookTask $task */
                AnsiblePlaybookRunTaskHost::firstOrCreate([
                    'ansible_playbook_run_id'  => $run->id,
                    'ansible_playbook_task_id' => $task->id,
                    'host'                     => self::PENDING_HOST_PLACEHOLDER,
                    'task_name'                => $task->name,
                ], [
                    'module'   => $task->module,
                    'position' => $task->position,
                    'status'   => AnsiblePlaybookRunTaskHost::STATUS_PENDING,
                ]);
            }
            return;
        }

        AnsiblePlaybookRunTaskHost::where('ansible_playbook_run_id', $run->id)
            ->where('host', self::PENDING_HOST_PLACEHOLDER)
            ->delete();

        // Build task-name → resolved-hosts map by reading the playbook YAML.
        // This ensures tasks scoped to `hosts: overseer` only get one PENDING
        // row, while tasks scoped to `hosts: all` get one per inventory host.
        $taskHostMap = $this->buildTaskHostMapFromPlaybook(
            $run->activity?->playbook_yaml ?? null,
            $tasks,
            $groupHosts,
            $allHosts,
        );

        foreach ($tasks as $task) {
            /** @var AnsiblePlaybookTask $task */
            $hosts = $taskHostMap[mb_strtolower($task->name)] ?? $allHosts;

            foreach ($hosts as $host) {
                AnsiblePlaybookRunTaskHost::firstOrCreate([
                    'ansible_playbook_run_id'  => $run->id,
                    'ansible_playbook_task_id' => $task->id,
                    'host'                     => $host,
                    'task_name'                => $task->name,
                ], [
                    'module'   => $task->module,
                    'position' => $task->position,
                    'status'   => AnsiblePlaybookRunTaskHost::STATUS_PENDING,
                ]);
            }
        }
    }

    public function syncFromLogs(AnsiblePlaybookRun $run): void
    {
        $run->loadMissing('activity.tasks', 'taskHostStatuses');

        $tasksByName = [];
        foreach (($run->activity?->tasks ?? []) as $task) {
            $tasksByName[mb_strtolower($task->name)] = $task;
        }

        $logs = $this->runLogRepository->listByActivityRun((string) $run->id);

        $currentTaskName = null;
        foreach ($logs as $log) {
            $line = trim((string) $log->message);

            if (preg_match('/^TASK \[(.+?)\] \*+$/', $line, $m)) {
                $currentTaskName = trim($m[1]);
                $this->markTaskRunning($run, $currentTaskName, $tasksByName);
                continue;
            }

            if (! $currentTaskName) {
                continue;
            }

            if (preg_match('/^(ok|changed|skipping): \[([^\]]+)\]/i', $line, $m)) {
                $host   = trim($m[2]);
                $status = match (strtolower($m[1])) {
                    'ok'       => AnsiblePlaybookRunTaskHost::STATUS_OK,
                    'changed'  => AnsiblePlaybookRunTaskHost::STATUS_CHANGED,
                    'skipping' => AnsiblePlaybookRunTaskHost::STATUS_SKIPPED,
                    default    => AnsiblePlaybookRunTaskHost::STATUS_OK,
                };

                $this->updateTaskHost($run, $currentTaskName, $host, $status, $line, $tasksByName);
                continue;
            }

            if (preg_match('/^fatal: \[([^\]]+)\]: UNREACHABLE!/i', $line, $m)) {
                $this->updateTaskHost(
                    $run,
                    $currentTaskName,
                    trim($m[1]),
                    AnsiblePlaybookRunTaskHost::STATUS_UNREACHABLE,
                    $line,
                    $tasksByName,
                );
                continue;
            }

            if (preg_match('/^fatal: \[([^\]]+)\]: FAILED!/i', $line, $m)) {
                $this->updateTaskHost(
                    $run,
                    $currentTaskName,
                    trim($m[1]),
                    AnsiblePlaybookRunTaskHost::STATUS_FAILED,
                    $line,
                    $tasksByName,
                );
            }
        }
    }

    private function parseInventoryHosts(?string $inventoryIni): array
    {
        if (! $inventoryIni) {
            return [];
        }

        $hosts = [];
        foreach (preg_split('/\R/', $inventoryIni) as $line) {
            $line = trim((string) $line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '[')) {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            $host  = trim((string) ($parts[0] ?? ''));
            if ($host !== '') {
                $hosts[] = $host;
            }
        }

        return array_values(array_unique($hosts));
    }

    /**
     * Returns a map of group name → list of hosts.
     * The 'all' key always contains every host.
     */
    private function parseInventoryGroupHosts(?string $inventoryIni): array
    {
        $groups       = ['all' => []];
        $currentGroup = 'all';

        if (! $inventoryIni) {
            return $groups;
        }

        foreach (preg_split('/\R/', $inventoryIni) as $raw) {
            $line = trim((string) $raw);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^\[([^\]]+)\]$/', $line, $m)) {
                $section = $m[1];
                // Skip :vars and :children meta-sections
                if (str_contains($section, ':')) {
                    $currentGroup = '__skip__';
                    continue;
                }
                $currentGroup = $section;
                if (! isset($groups[$currentGroup])) {
                    $groups[$currentGroup] = [];
                }
                continue;
            }

            if ($currentGroup === '__skip__') {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            $host  = trim((string) ($parts[0] ?? ''));
            if ($host === '') {
                continue;
            }

            $groups[$currentGroup][] = $host;
            if (! in_array($host, $groups['all'], true)) {
                $groups['all'][] = $host;
            }
        }

        return $groups;
    }

    /**
     * Parses the playbook YAML and returns a map of
     * lowercase-task-name → resolved host list.
     *
     * Tasks whose play's `hosts` field cannot be resolved fall back to $allHosts.
     */
    private function buildTaskHostMapFromPlaybook(
        ?string $playbookYaml,
        $tasks,
        array $groupHosts,
        array $allHosts,
    ): array {
        if (! $playbookYaml) {
            return [];
        }

        try {
            $plays = \Symfony\Component\Yaml\Yaml::parse($playbookYaml);
        } catch (\Throwable) {
            return [];
        }

        if (! is_array($plays)) {
            return [];
        }

        $taskHostMap = [];

        foreach ($plays as $play) {
            if (! is_array($play)) {
                continue;
            }

            $hostsPattern  = (string) ($play['hosts'] ?? 'all');
            $resolvedHosts = $this->resolveHostsFromInventory($hostsPattern, $groupHosts, $allHosts);

            foreach (['pre_tasks', 'tasks', 'post_tasks', 'handlers'] as $section) {
                foreach ($play[$section] ?? [] as $task) {
                    if (! is_array($task)) {
                        continue;
                    }
                    $taskName = $task['name'] ?? null;
                    if ($taskName) {
                        $taskHostMap[mb_strtolower((string) $taskName)] = $resolvedHosts;
                    }
                }
            }
        }

        return $taskHostMap;
    }

    /**
     * Resolves a play's `hosts` pattern against the parsed inventory groups.
     */
    private function resolveHostsFromInventory(string $pattern, array $groupHosts, array $allHosts): array
    {
        $pattern = trim($pattern);

        if ($pattern === 'all' || $pattern === '*') {
            return $allHosts;
        }

        // Comma-separated multi-group
        if (str_contains($pattern, ',')) {
            $resolved = [];
            foreach (explode(',', $pattern) as $part) {
                $resolved = array_merge(
                    $resolved,
                    $this->resolveHostsFromInventory(trim($part), $groupHosts, $allHosts),
                );
            }
            return array_values(array_unique($resolved));
        }

        // Negation patterns (e.g. !groupname) – unsupported, fall back to all
        if (str_starts_with($pattern, '!')) {
            return $allHosts;
        }

        // Exact group match
        if (isset($groupHosts[$pattern])) {
            return $groupHosts[$pattern];
        }

        // Direct host name
        if (in_array($pattern, $allHosts, true)) {
            return [$pattern];
        }

        // Wildcard / glob – fall back to all hosts
        return $allHosts;
    }

    private function loadTasksByName(AnsiblePlaybookRun $run): array
    {
        $run->loadMissing('activity.tasks');

        $map = [];
        foreach (($run->activity?->tasks ?? []) as $task) {
            $map[mb_strtolower($task->name)] = $task;
        }

        $this->tasksByNameByRun[(int) $run->id] = $map;

        return $map;
    }

    private function markTaskRunning(AnsiblePlaybookRun $run, string $taskName, array $tasksByName): void
    {
        // Intentionally a no-op: we cannot know which hosts a task targets
        // until Ansible emits per-host result lines (ok/changed/failed/etc.).
        // Bulk-marking all initialized hosts as RUNNING would incorrectly show
        // hosts that belong to a different play's host group (e.g. every host
        // would appear as RUNNING for a task that only targets [overseer]).
        // Each host row transitions from PENDING to its real status when
        // updateTaskHost() processes the per-host output line.
    }

    private function updateTaskHost(
        AnsiblePlaybookRun $run,
        string $taskName,
        string $host,
        string $status,
        string $line,
        array $tasksByName,
    ): void {
        $task = $tasksByName[mb_strtolower($taskName)] ?? null;

        $attrs = [
            'ansible_playbook_run_id' => $run->id,
            'host'                    => $host,
            'task_name'               => $taskName,
        ];

        if ($task) {
            $attrs['ansible_playbook_task_id'] = $task->id;
            $values = [
                'module'   => $task->module,
                'position' => $task->position,
            ];

            AnsiblePlaybookRunTaskHost::where('ansible_playbook_run_id', $run->id)
                ->where('ansible_playbook_task_id', $task->id)
                ->where('host', self::PENDING_HOST_PLACEHOLDER)
                ->delete();
        } else {
            $values = [];

            AnsiblePlaybookRunTaskHost::where('ansible_playbook_run_id', $run->id)
                ->where('task_name', $taskName)
                ->where('host', self::PENDING_HOST_PLACEHOLDER)
                ->delete();
        }

        $row = AnsiblePlaybookRunTaskHost::firstOrCreate($attrs, array_merge($values, [
            'status' => AnsiblePlaybookRunTaskHost::STATUS_PENDING,
        ]));

        $row->status = $status;
        $row->output = $line;
        $row->started_at = $row->started_at ?? now();
        $row->finished_at = now();
        $row->save();

        if ($status === AnsiblePlaybookRunTaskHost::STATUS_UNREACHABLE) {
            $this->markHostPendingTasksAsUnreachable($run, $host, $task, $line);
        }
    }

    private function markHostPendingTasksAsUnreachable(
        AnsiblePlaybookRun $run,
        string $host,
        ?AnsiblePlaybookTask $currentTask,
        string $line,
    ): void {
        $query = AnsiblePlaybookRunTaskHost::query()
            ->where('ansible_playbook_run_id', $run->id)
            ->where('host', $host)
            ->where('status', AnsiblePlaybookRunTaskHost::STATUS_PENDING);

        if ($currentTask?->position !== null) {
            $query->where('position', '>=', $currentTask->position);
        }

        $query->update([
            'status'      => AnsiblePlaybookRunTaskHost::STATUS_UNREACHABLE,
            'output'      => $line,
            'started_at'  => now(),
            'finished_at' => now(),
            'updated_at'  => now(),
        ]);
    }
}
