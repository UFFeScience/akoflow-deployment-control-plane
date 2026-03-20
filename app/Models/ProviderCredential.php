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
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProviderCredentialValue::class, 'provider_credential_id');
    }
}
