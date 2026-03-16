<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExperimentTemplateVersion extends Model
{
    protected $table = 'experiment_template_versions';
    protected $fillable = ['template_id','version','definition_json','is_active'];
    public $timestamps = false;

    protected $casts = [
        'definition_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ExperimentTemplate::class, 'template_id');
    }

    public function terraformModules(): HasMany
    {
        return $this->hasMany(ExperimentTemplateTerraformModule::class, 'template_version_id');
    }
}