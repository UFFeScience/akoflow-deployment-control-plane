<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProvisionedResourceType extends Model
{
    protected $table = 'provisioned_resource_types';

    protected $fillable = [
        'provisioned_resource_kind_id',
        'provider_id',
        'slug',
        'name',
        'provider_resource_identifier',
        'attributes_schema_json',
        'is_active',
    ];

    protected $casts = [
        'attributes_schema_json' => 'array',
        'is_active'              => 'boolean',
    ];

    public function kind(): BelongsTo
    {
        return $this->belongsTo(ProvisionedResourceKind::class, 'provisioned_resource_kind_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ProvisionedResource::class, 'provisioned_resource_type_id');
    }
}
