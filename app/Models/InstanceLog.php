<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstanceLog extends Model
{
	protected $table = 'instance_logs';
	protected $fillable = ['provisioned_instance_id','level','message'];
	public $timestamps = false;

	public const LEVELS = ['INFO', 'WARN', 'ERROR'];

	public function instance(): BelongsTo
	{
		return $this->belongsTo(ProvisionedInstance::class, 'provisioned_instance_id');
	}
}
