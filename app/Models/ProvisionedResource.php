<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProvisionedResource extends Model
{
    protected $table = 'provisioned_resources';

    protected $fillable = [
        'deployment_id',
        'provisioned_resource_type_id',
        'provider_resource_id',
        'name',
        'status',
        'health_status',
        'last_health_check_at',
        'public_ip',
        'private_ip',
        'metadata_json',
    ];

    protected $casts = [
        'last_health_check_at' => 'datetime',
        'metadata_json'        => 'array',
    ];

    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_CREATING  = 'CREATING';
    public const STATUS_RUNNING   = 'RUNNING';
    public const STATUS_STOPPING  = 'STOPPING';
    public const STATUS_STOPPED   = 'STOPPED';
    public const STATUS_ERROR     = 'ERROR';
    public const STATUS_DESTROYED = 'DESTROYED';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CREATING,
        self::STATUS_RUNNING,
        self::STATUS_STOPPING,
        self::STATUS_STOPPED,
        self::STATUS_ERROR,
        self::STATUS_DESTROYED,
    ];

    public const HEALTH_STATUSES = ['HEALTHY', 'UNHEALTHY', 'UNKNOWN'];

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class, 'deployment_id');
    }

    public function resourceType(): BelongsTo
    {
        return $this->belongsTo(ProvisionedResourceType::class, 'provisioned_resource_type_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ResourceLog::class, 'provisioned_resource_id');
    }
}
