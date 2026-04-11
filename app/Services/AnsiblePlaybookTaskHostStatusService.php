<?php

namespace App\Services;

use App\Models\AnsiblePlaybookRun;
use App\Models\AnsiblePlaybookRunTaskHost;
use App\Models\AnsiblePlaybookTask;
use App\Repositories\RunLogRepository;

class AnsiblePlaybookTaskHostStatusService
{
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

        $hosts = $this->parseInventoryHosts($run->inventory_ini ?? null);
        if (empty($hosts)) {
            $hosts = ['all'];
        }

        foreach ($tasks as $task) {
            /** @var AnsiblePlaybookTask $task */
            foreach ($hosts as $host) {
                AnsiblePlaybookRunTaskHost::firstOrCreate([
                    'ansible_playbook_run_id' => $run->id,
                    'ansible_playbook_task_id'=> $task->id,
                    'host'                    => $host,
                    'task_name'               => $task->name,
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
        $task = $tasksByName[mb_strtolower($taskName)] ?? null;

        if ($task) {
            $query = AnsiblePlaybookRunTaskHost::where('ansible_playbook_run_id', $run->id)
                ->where('ansible_playbook_task_id', $task->id);
        } else {
            $query = AnsiblePlaybookRunTaskHost::where('ansible_playbook_run_id', $run->id)
                ->where('task_name', $taskName);
        }

        $query
            ->where('status', AnsiblePlaybookRunTaskHost::STATUS_PENDING)
            ->update([
                'status'     => AnsiblePlaybookRunTaskHost::STATUS_RUNNING,
                'started_at' => now(),
            ]);
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
        } else {
            $values = [];
        }

        $row = AnsiblePlaybookRunTaskHost::firstOrCreate($attrs, array_merge($values, [
            'status' => AnsiblePlaybookRunTaskHost::STATUS_PENDING,
        ]));

        $row->status = $status;
        $row->output = $line;
        $row->started_at = $row->started_at ?? now();
        $row->finished_at = now();
        $row->save();
    }
}
