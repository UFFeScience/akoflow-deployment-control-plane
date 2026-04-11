<?php

namespace App\Models;

use App\Contracts\HasRunLog;
use App\Repositories\RunLogRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnsiblePlaybookRun extends Model implements HasRunLog
{
    protected $table = 'ansible_activity_runs';

    protected $fillable = [
        'deployment_id',
        'playbook_id',
        'playbook_name',
        'trigger',
        'status',
        'provider_type',
        'triggered_by',
        'workspace_path',
        'extra_vars_json',
        'inventory_ini',
        'output_json',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'extra_vars_json' => 'array',
        'output_json'     => 'array',
        'started_at'      => 'datetime',
        'finished_at'     => 'datetime',
    ];

    public const STATUS_QUEUED       = 'QUEUED';
    public const STATUS_INITIALIZING = 'INITIALIZING';
    public const STATUS_RUNNING      = 'RUNNING';
    public const STATUS_COMPLETED    = 'COMPLETED';
    public const STATUS_FAILED       = 'FAILED';

    // ─── Relations ────────────────────────────────────────────────────────────

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class, 'deployment_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(AnsiblePlaybook::class, 'playbook_id');
    }

    public function taskHostStatuses(): HasMany
    {
        return $this->hasMany(AnsiblePlaybookRunTaskHost::class, 'ansible_playbook_run_id')
            ->orderBy('position')
            ->orderBy('host');
    }

    // ─── HasRunLog ────────────────────────────────────────────────────────────

    public function appendLog(string $line): void
    {
        $level = RunLog::inferLevel($line);
        app(RunLogRepository::class)->createForPlaybookRun($this, $level, $line);
        error_log($line);
    }
}
