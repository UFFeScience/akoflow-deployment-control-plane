<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstanceGroup extends Model
{
    protected $table = 'instance_groups';

    protected $fillable = [
        'cluster_id',
        'instance_type_id',
        'role',
        'quantity',
        'metadata_json',
    ];

    protected $casts = [
        'metadata_json' => 'array',
    ];

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class, 'cluster_id');
    }

    public function instanceType(): BelongsTo
    {
        return $this->belongsTo(InstanceType::class, 'instance_type_id');
    }
}
