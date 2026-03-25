<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeploymentProviderCredential extends Model
{
    protected $table = 'deployment_provider_credentials';

    protected $fillable = [
        'deployment_id',
        'provider_id',
        'provider_credential_id',
        'provider_slug',
    ];

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class, 'deployment_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(ProviderCredential::class, 'provider_credential_id');
    }
}
