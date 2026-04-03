<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentTemplateTerraformModule extends Model
{
    protected $table = 'environment_template_terraform_modules';

    protected $fillable = [
        'provider_configuration_id',
        'module_slug',
        'main_tf',
        'variables_tf',
        'outputs_tf',
        'tfvars_mapping_json',
        'outputs_mapping_json',
        'credential_env_keys',
    ];

    protected $casts = [
        'tfvars_mapping_json'  => 'array',
        'outputs_mapping_json' => 'array',
        'credential_env_keys'  => 'array',
    ];

    public const BUILT_IN_SLUGS = ['aws_nvflare', 'gcp_gke'];

    public function hasCustomHcl(): bool
    {
        return !empty($this->main_tf);
    }

    public function isBuiltIn(): bool
    {
        return !$this->hasCustomHcl() && !empty($this->module_slug);
    }

    public function providerConfiguration(): BelongsTo
    {
        return $this->belongsTo(EnvironmentTemplateProviderConfiguration::class, 'provider_configuration_id');
    }
}
