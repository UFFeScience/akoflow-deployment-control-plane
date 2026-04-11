<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deployment extends Model
{
    // Multi-provider support: see deployment_provider_credentials table.
    protected $table = 'deployments';

    protected $fillable = [
        'environment_id',
        'deployment_template_id',
        'region',
        'environment_type',
        'name',
        'status',
    ];

    public const STATUS_PROVISIONING = 'PROVISIONING';
    public const STATUS_CONFIGURING  = 'CONFIGURING';
    public const STATUS_RUNNING      = 'RUNNING';
    public const STATUS_STOPPED      = 'STOPPED';
    public const STATUS_ERROR        = 'ERROR';
    public const STATUS_DESTROYING   = 'DESTROYING';

    public const STATUSES = [
        self::STATUS_PROVISIONING,
        self::STATUS_CONFIGURING,
        self::STATUS_RUNNING,
        self::STATUS_STOPPED,
        self::STATUS_ERROR,
        self::STATUS_DESTROYING,
    ];

    public const ENVIRONMENT_TYPES = ['CLOUD', 'ON_PREM', 'HPC'];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DeploymentTemplate::class, 'deployment_template_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ProvisionedResource::class, 'deployment_id');
    }

    public function providerCredentials(): HasMany
    {
        return $this->hasMany(DeploymentProviderCredential::class, 'deployment_id');
    }
}
