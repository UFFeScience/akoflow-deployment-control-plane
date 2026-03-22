<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceLog extends Model
{
    protected $table = 'resource_logs';

    protected $fillable = ['provisioned_resource_id', 'level', 'message'];

    // Append-only — only created_at is stored
    public $timestamps = false;

    protected $casts = ['created_at' => 'datetime'];

    public const LEVEL_DEBUG = 'DEBUG';
    public const LEVEL_INFO  = 'INFO';
    public const LEVEL_WARN  = 'WARN';
    public const LEVEL_ERROR = 'ERROR';

    public const LEVELS = [
        self::LEVEL_DEBUG,
        self::LEVEL_INFO,
        self::LEVEL_WARN,
        self::LEVEL_ERROR,
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(ProvisionedResource::class, 'provisioned_resource_id');
    }
}
