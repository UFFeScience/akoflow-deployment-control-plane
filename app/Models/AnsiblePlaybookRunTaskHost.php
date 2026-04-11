<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnsiblePlaybookRunTaskHost extends Model
{
    protected $table = 'ansible_playbook_run_task_hosts';

    protected $fillable = [
        'ansible_playbook_run_id',
        'ansible_playbook_task_id',
        'host',
        'task_name',
        'module',
        'position',
        'status',
        'output',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'position'    => 'integer',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public const STATUS_PENDING     = 'PENDING';
    public const STATUS_RUNNING     = 'RUNNING';
    public const STATUS_OK          = 'OK';
    public const STATUS_CHANGED     = 'CHANGED';
    public const STATUS_FAILED      = 'FAILED';
    public const STATUS_SKIPPED     = 'SKIPPED';
    public const STATUS_UNREACHABLE = 'UNREACHABLE';

    public function run(): BelongsTo
    {
        return $this->belongsTo(AnsiblePlaybookRun::class, 'ansible_playbook_run_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(AnsiblePlaybookTask::class, 'ansible_playbook_task_id');
    }
}
