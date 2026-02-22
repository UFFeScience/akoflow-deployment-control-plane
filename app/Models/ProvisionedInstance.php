<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProvisionedInstance extends Model
{
	protected $table = 'provisioned_instances';
	protected $fillable = ['cluster_id','instance_type_id','provider_instance_id','role','status','health_status','last_health_check_at','public_ip','private_ip'];

	protected $casts = ['last_health_check_at' => 'datetime'];

	public const ROLES = ['master', 'worker', 'client', 'server'];
	public const STATUSES = ['PROVISIONING', 'RUNNING', 'STOPPED', 'TERMINATED', 'ERROR'];
	public const HEALTH_STATUSES = ['HEALTHY', 'UNHEALTHY'];

	public function cluster(): BelongsTo
	{
		return $this->belongsTo(Cluster::class, 'cluster_id');
	}

	public function instanceType(): BelongsTo
	{
		return $this->belongsTo(InstanceType::class, 'instance_type_id');
	}

	public function logs(): HasMany
	{
		return $this->hasMany(InstanceLog::class, 'provisioned_instance_id');
	}
}
