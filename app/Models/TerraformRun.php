<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerraformRun extends Model
{
    protected $table = 'terraform_runs';

    protected $fillable = [
        'environment_id',
        'status',
        'provider_type',
        'action',
        'workspace_path',
        'tfvars_json',
        'output_json',
        'logs',
        'started_at',
        'finished_at',
    ];

    public const STATUS_QUEUED       = 'QUEUED';
    public const STATUS_INITIALIZING = 'INITIALIZING';
    public const STATUS_PLANNING     = 'PLANNING';
    public const STATUS_APPLYING     = 'APPLYING';
    public const STATUS_APPLIED      = 'APPLIED';
    public const STATUS_DESTROYING   = 'DESTROYING';
    public const STATUS_DESTROYED    = 'DESTROYED';
    public const STATUS_FAILED       = 'FAILED';

    public const ACTION_APPLY   = 'apply';
    public const ACTION_DESTROY = 'destroy';

    protected $casts = [
        'tfvars_json'  => 'array',
        'output_json'  => 'array',
        'started_at'   => 'datetime',
        'finished_at'  => 'datetime',
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    public function appendLog(string $line): void
    {
        $this->logs = ($this->logs ?? '') . $line . "\n";
        $this->save();
    }
}
