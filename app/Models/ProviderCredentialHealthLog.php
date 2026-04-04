<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderCredentialHealthLog extends Model
{
    protected $table = 'provider_credential_health_logs';

    protected $fillable = [
        'provider_credential_id',
        'health_status',
        'health_message',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function credential(): BelongsTo
    {
        return $this->belongsTo(ProviderCredential::class, 'provider_credential_id');
    }
}
