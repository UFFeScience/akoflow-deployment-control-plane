<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderCredential extends Model
{
    protected $table = 'provider_credentials';

    protected $fillable = [
        'provider_id',
        'name',
        'slug',
        'description',
        'is_active',
        'health_check_template',
        'health_status',
        'health_message',
        'last_health_check_at',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'last_health_check_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProviderCredentialValue::class, 'provider_credential_id');
    }

    public function healthLogs(): HasMany
    {
        return $this->hasMany(ProviderCredentialHealthLog::class, 'provider_credential_id')->orderByDesc('checked_at');
    }
}
