<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunLog extends Model
{
    protected $table = 'run_logs';

    public $timestamps = false;

    protected $fillable = [
        'terraform_run_id',
        'ansible_run_id',
        'provisioned_resource_id',
        'environment_id',
        'source',
        'level',
        'message',
    ];

    protected $casts = ['created_at' => 'datetime'];

    // ── Source constants ──────────────────────────────────────────────────────
    public const SOURCE_TERRAFORM = 'terraform';
    public const SOURCE_ANSIBLE   = 'ansible';
    public const SOURCE_RESOURCE  = 'resource';

    // ── Level constants ───────────────────────────────────────────────────────
    public const LEVEL_DEBUG = 'DEBUG';
    public const LEVEL_INFO  = 'INFO';
    public const LEVEL_WARN  = 'WARN';
    public const LEVEL_ERROR = 'ERROR';

    // ── Relationships ─────────────────────────────────────────────────────────
    public function terraformRun(): BelongsTo
    {
        return $this->belongsTo(TerraformRun::class, 'terraform_run_id');
    }

    public function ansibleRun(): BelongsTo
    {
        return $this->belongsTo(AnsibleRun::class, 'ansible_run_id');
    }

    public function provisionedResource(): BelongsTo
    {
        return $this->belongsTo(ProvisionedResource::class, 'provisioned_resource_id');
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Infer log level from line content (same heuristic as the old frontend parser).
     */
    public static function inferLevel(string $line): string
    {
        $lower = strtolower($line);

        if (str_contains($lower, '[error]')  ||
            str_contains($lower, 'error:')   ||
            str_contains($lower, 'failed')) {
            return self::LEVEL_ERROR;
        }

        if (str_contains($lower, '[warning]') ||
            str_contains($lower, 'warn:')     ||
            str_contains($lower, 'warning')) {
            return self::LEVEL_WARN;
        }

        if (str_contains($lower, '[debug]') || str_contains($lower, 'debug:')) {
            return self::LEVEL_DEBUG;
        }

        return self::LEVEL_INFO;
    }
}
