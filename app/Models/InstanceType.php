<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstanceType extends Model
{
	protected $table = 'instance_types';
	protected $fillable = ['provider_id','name','vcpus','memory_mb','gpu_count','storage_default_gb','network_bandwidth','region','status','is_active'];

	public const STATUSES = ['AVAILABLE', 'UNAVAILABLE', 'DEPRECATED'];

	protected $casts = [
		'is_active' => 'boolean',
	];

	public function provider(): BelongsTo
	{
		return $this->belongsTo(Provider::class, 'provider_id');
	}

	public function provisionedInstances(): HasMany
	{
		return $this->hasMany(ProvisionedInstance::class, 'instance_type_id');
	}
}
