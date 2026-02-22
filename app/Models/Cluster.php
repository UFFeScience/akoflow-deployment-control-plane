<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\InstanceGroup;

class Cluster extends Model
{
	protected $table = 'clusters';
	protected $fillable = ['experiment_id','cluster_template_id','provider_id','region','environment_type','name','status'];

	public const STATUSES = ['PROVISIONING', 'RUNNING', 'STOPPED', 'ERROR'];
	public const ENVIRONMENT_TYPES = ['CLOUD', 'ON_PREM', 'HPC'];

	public function experiment(): BelongsTo
	{
		return $this->belongsTo(Experiment::class, 'experiment_id');
	}

	public function template(): BelongsTo
	{
		return $this->belongsTo(ClusterTemplate::class, 'cluster_template_id');
	}

	public function provider(): BelongsTo
	{
		return $this->belongsTo(Provider::class, 'provider_id');
	}

	public function instances(): HasMany
	{
		return $this->hasMany(ProvisionedInstance::class, 'cluster_id');
	}

	public function instanceGroups(): HasMany
	{
		return $this->hasMany(InstanceGroup::class, 'cluster_id');
	}

	public function scalingEvents(): HasMany
	{
		return $this->hasMany(ClusterScalingEvent::class, 'cluster_id');
	}
}
