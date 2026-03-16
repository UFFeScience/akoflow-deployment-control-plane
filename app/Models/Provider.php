<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
	protected $table = 'providers';
	protected $fillable = ['name', 'slug', 'default_module_slug', 'description', 'type', 'status', 'health_status', 'health_message', 'last_health_check_at'];

	public const TYPES = ['CLOUD', 'ON_PREM', 'HPC'];
	public const STATUSES = ['ACTIVE', 'DEGRADED', 'DOWN', 'MAINTENANCE'];
	public const HEALTH_STATUSES = ['HEALTHY', 'UNHEALTHY'];

	protected $casts = [
		'last_health_check_at' => 'datetime',
	];

	public function instanceTypes(): HasMany
	{
		return $this->hasMany(InstanceType::class, 'provider_id');
	}

	public function clusters(): HasMany
	{
		return $this->hasMany(Cluster::class, 'provider_id');
	}

	public function credentials(): HasMany
	{
		return $this->hasMany(ProviderCredential::class, 'provider_id');
	}
}
