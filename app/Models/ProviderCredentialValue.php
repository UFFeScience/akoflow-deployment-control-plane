<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderCredentialValue extends Model
{
    protected $table = 'provider_credential_values';

    protected $fillable = [
        'provider_credential_id',
        'field_key',
        'field_value',
    ];

    public function credential(): BelongsTo
    {
        return $this->belongsTo(ProviderCredential::class, 'provider_credential_id');
    }
}
