<?php

namespace App\Models;

use App\Repositories\RunLogRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Contracts\HasRunLog;

class RunbookRun extends Model implements HasRunLog
{
    protected $table = 'runbook_runs';

    protected $fillable = [
        'deployment_id',
        'runbook_id',
        'runbook_name',
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

    public const STATUS_QUEUED       = 'QUEUED';
    public const STATUS_INITIALIZING = 'INITIALIZING';
    public const STATUS_RUNNING      = 'RUNNING';
    public const STATUS_COMPLETED    = 'COMPLETED';
    public const STATUS_FAILED       = 'FAILED';

    protected $casts = [
        'extra_vars_json' => 'array',
        'output_json'     => 'array',
        'started_at'      => 'datetime',
        'finished_at'     => 'datetime',
    ];

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class, 'deployment_id');
    }

    public function runbook(): BelongsTo
    {
        return $this->belongsTo(EnvironmentTemplateRunbook::class, 'runbook_id');
    }

    public function taskRuns(): HasMany
    {
        return $this->hasMany(AnsibleTaskRun::class, 'runbook_run_id')->orderBy('position');
    }

    public function appendLog(string $line): void
    {
        $level = RunLog::inferLevel($line);
        app(RunLogRepository::class)->createForRunbookRun($this, $level, $line);
        error_log($line);
    }
}
