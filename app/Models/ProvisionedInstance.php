<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProvisionedInstance extends Model
{
	protected $table = 'provisioned_instances';
	protected $fillable = ['cluster_id','instance_group_id','instance_type_id','provider_instance_id','role','status','health_status','last_health_check_at','public_ip','private_ip'];

	protected $casts = ['last_health_check_at' => 'datetime'];

	public const ROLES = ['master', 'worker', 'client', 'server'];
	public const STATUS_PROVISIONING = 'PROVISIONING';
	public const STATUS_RUNNING = 'RUNNING';
	public const STATUS_STOPPED = 'STOPPED';
	public const STATUS_TERMINATED = 'TERMINATED';
	public const STATUS_REMOVING = 'REMOVING';
	public const STATUS_ERROR = 'ERROR';
	public const STATUSES = [
		self::STATUS_PROVISIONING,
		self::STATUS_RUNNING,
		self::STATUS_STOPPED,
		self::STATUS_TERMINATED,
		self::STATUS_REMOVING,
		self::STATUS_ERROR,
	];
	public const STATUSES_PROVISIONING = [self::STATUS_PROVISIONING];
	public const HEALTH_STATUSES = ['HEALTHY', 'UNHEALTHY'];

	public function cluster(): BelongsTo
	{
		return $this->belongsTo(Cluster::class, 'cluster_id');
	}

	public function instanceGroup(): BelongsTo
	{
		return $this->belongsTo(InstanceGroup::class, 'instance_group_id');
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
