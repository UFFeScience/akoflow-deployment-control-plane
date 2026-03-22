<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClusterTemplate extends Model
{
    protected $table = 'cluster_templates';
    protected $fillable = ['template_version_id','custom_parameters_json'];
    public $timestamps = false;

    protected $casts = ['custom_parameters_json' => 'array'];

    public function version(): BelongsTo
    {
        return $this->belongsTo(EnvironmentTemplateVersion::class, 'template_version_id');
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class, 'cluster_template_id');
    }
}
