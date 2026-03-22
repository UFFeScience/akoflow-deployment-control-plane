<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentTemplateTerraformModule extends Model
{
    protected $table = 'environment_template_terraform_modules';

    protected $fillable = [
        'template_version_id',
        'module_slug',
        'provider_type',
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

    /** Os módulos built-in disponíveis na plataforma. */
    public const BUILT_IN_SLUGS = ['aws_nvflare', 'gcp_gke'];

    /** Verdadeiro se main_tf foi preenchido manualmente. */
    public function hasCustomHcl(): bool
    {
        return !empty($this->main_tf);
    }

    /** Verdadeiro se referencia um módulo built-in sem HCL custom. */
    public function isBuiltIn(): bool
    {
        return !$this->hasCustomHcl() && !empty($this->module_slug);
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(EnvironmentTemplateVersion::class, 'template_version_id');
    }
}
