<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProvisionedResourceKind extends Model
{
    protected $table = 'provisioned_resource_kinds';

    protected $fillable = ['slug', 'name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public const SLUG_COMPUTE    = 'compute';
    public const SLUG_STORAGE    = 'storage';
    public const SLUG_SERVERLESS = 'serverless';
    public const SLUG_DATABASE   = 'database';
    public const SLUG_NETWORK    = 'network';
    public const SLUG_CONTAINER  = 'container';

    public function types(): HasMany
    {
        return $this->hasMany(ProvisionedResourceType::class, 'provisioned_resource_kind_id');
    }
}
