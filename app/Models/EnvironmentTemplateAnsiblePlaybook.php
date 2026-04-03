<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentTemplateAnsiblePlaybook extends Model
{
    protected $table = 'environment_template_ansible_playbooks';

    protected $fillable = [
        'provider_configuration_id',
        'playbook_slug',
        'playbook_yaml',
        'inventory_template',
        'vars_mapping_json',
        'outputs_mapping_json',
        'credential_env_keys',
        'roles_json',
    ];

    protected $casts = [
        'vars_mapping_json'    => 'array',
        'outputs_mapping_json' => 'array',
        'credential_env_keys'  => 'array',
        'roles_json'           => 'array',
    ];

    public const BUILT_IN_SLUGS = ['hpc_slurm_kind', 'hpc_akoflow', 'on_prem_kind'];

    public function hasCustomPlaybook(): bool
    {
        return !empty($this->playbook_yaml);
    }

    public function isBuiltIn(): bool
    {
        return !$this->hasCustomPlaybook() && !empty($this->playbook_slug);
    }

    public function providerConfiguration(): BelongsTo
    {
        return $this->belongsTo(EnvironmentTemplateProviderConfiguration::class, 'provider_configuration_id');
    }
}
