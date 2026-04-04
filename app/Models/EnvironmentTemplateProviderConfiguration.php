<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EnvironmentTemplateProviderConfiguration extends Model
{
    protected $table = 'environment_template_provider_configurations';

    protected $fillable = [
        'template_version_id',
        'name',
        'applies_to_providers',
    ];

    protected $casts = [
        'applies_to_providers' => 'array',
    ];

    public function isDefault(): bool
    {
        return empty($this->applies_to_providers);
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(EnvironmentTemplateVersion::class, 'template_version_id');
    }

    public function terraformModule(): HasOne
    {
        return $this->hasOne(EnvironmentTemplateTerraformModule::class, 'provider_configuration_id');
    }

    public function ansiblePlaybook(): HasOne
    {
        return $this->hasOne(EnvironmentTemplateAnsiblePlaybook::class, 'provider_configuration_id');
    }

    public function runbooks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EnvironmentTemplateRunbook::class, 'provider_configuration_id')->orderBy('position');
    }
}
