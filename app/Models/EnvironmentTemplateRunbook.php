<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnvironmentTemplateRunbook extends Model
{
    protected $table = 'environment_template_runbooks';

    protected $fillable = [
        'provider_configuration_id',
        'name',
        'description',
        'playbook_yaml',
        'vars_mapping_json',
        'credential_env_keys',
        'roles_json',
        'position',
    ];

    protected $casts = [
        'vars_mapping_json'  => 'array',
        'credential_env_keys' => 'array',
        'roles_json'         => 'array',
        'position'           => 'integer',
    ];

    public function providerConfiguration(): BelongsTo
    {
        return $this->belongsTo(EnvironmentTemplateProviderConfiguration::class, 'provider_configuration_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AnsiblePlaybookTask::class, 'runbook_id')->orderBy('position');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(RunbookRun::class, 'runbook_id');
    }
}
