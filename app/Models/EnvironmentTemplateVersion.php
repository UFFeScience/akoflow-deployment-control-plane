<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 */
class EnvironmentTemplateVersion extends Model
{
    protected $table = 'environment_template_versions';
    protected $fillable = ['template_id','version','definition_json','is_active'];
    public $timestamps = false;

    protected $casts = [
        'definition_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EnvironmentTemplate::class, 'template_id');
    }

    public function terraformModules(): HasMany
    {
        return $this->hasMany(EnvironmentTemplateTerraformModule::class, 'template_version_id');
    }
}