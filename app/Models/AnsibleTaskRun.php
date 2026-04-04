<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnsibleTaskRun extends Model
{
    protected $table = 'ansible_task_runs';

    public $timestamps = false;

    protected $fillable = [
        'ansible_run_id',
        'runbook_run_id',
        'playbook_task_id',
        'task_name',
        'module',
        'position',
        'status',
        'output',
        'started_at',
        'finished_at',
    ];

    public const STATUS_PENDING     = 'PENDING';
    public const STATUS_RUNNING     = 'RUNNING';
    public const STATUS_OK          = 'OK';
    public const STATUS_FAILED      = 'FAILED';
    public const STATUS_SKIPPED     = 'SKIPPED';
    public const STATUS_UNREACHABLE = 'UNREACHABLE';

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
        'created_at'  => 'datetime',
        'position'    => 'integer',
    ];

    public function ansibleRun(): BelongsTo
    {
        return $this->belongsTo(AnsibleRun::class, 'ansible_run_id');
    }

    public function runbookRun(): BelongsTo
    {
        return $this->belongsTo(RunbookRun::class, 'runbook_run_id');
    }

    public function playbookTask(): BelongsTo
    {
        return $this->belongsTo(AnsiblePlaybookTask::class, 'playbook_task_id');
    }
}
