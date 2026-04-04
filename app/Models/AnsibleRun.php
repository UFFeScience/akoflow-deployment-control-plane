<?php

namespace App\Models;

use App\Repositories\RunLogRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Contracts\HasRunLog;

class AnsibleRun extends Model implements HasRunLog
{
    protected $table = 'ansible_runs';

    protected $fillable = [
        'deployment_id',
        'status',
        'action',
        'provider_type',
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

    public const ACTION_CONFIGURE = 'configure';
    public const ACTION_TEARDOWN  = 'teardown';

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

    public function appendLog(string $line): void
    {
        $level = RunLog::inferLevel($line);

        app(RunLogRepository::class)->createForAnsibleRun($this, $level, $line);

        error_log($line);
    }
}
