<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClusterScalingEvent extends Model
{
    protected $table = 'cluster_scaling_events';
    protected $fillable = ['cluster_id','action','old_value','new_value','triggered_by'];
    public $timestamps = false;

    public const ACTIONS = ['SCALE_UP', 'SCALE_DOWN'];
    public const TRIGGERED_BY = ['USER', 'SYSTEM', 'AKOFLOW'];

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class, 'cluster_id');
    }
}
