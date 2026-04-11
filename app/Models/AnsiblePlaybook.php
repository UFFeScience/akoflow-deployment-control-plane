<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * @property int    $id
 * @property string $name
 * @property string $trigger
 * @property string|null $playbook_yaml
 * @property string|null $inventory_template
 * @property array|null  $vars_mapping_json
 * @property array|null  $outputs_mapping_json
 * @property array|null  $roles_json
 * @property int    $position
 * @property bool   $enabled
 */

class AnsiblePlaybook extends Model
{
    protected $table = 'ansible_playbooks';

    protected $fillable = [
        'provider_configuration_id',
        'name',
        'description',
        'trigger',
        'playbook_slug',
        'playbook_yaml',
        'inventory_template',
        'vars_mapping_json',
        'outputs_mapping_json',
        'credential_env_keys',
        'roles_json',
        'position',
        'enabled',
    ];

    protected $casts = [
        'vars_mapping_json'    => 'array',
        'outputs_mapping_json' => 'array',
        'credential_env_keys'  => 'array',
        'roles_json'           => 'array',
        'position'             => 'int',
        'enabled'              => 'boolean',
    ];

    // ─── Trigger constants ────────────────────────────────────────────────────

    /** Fired automatically after Terraform provisioning completes (was: phase=provision). */
    public const TRIGGER_AFTER_PROVISION = 'after_provision';

    /** Fired automatically once ALL after_provision playbooks finish successfully. */
    public const TRIGGER_WHEN_READY = 'when_ready';

    /** Fired on-demand by the user (was: Runbook). */
    public const TRIGGER_MANUAL = 'manual';

    /** Fired automatically before Terraform destroy (was: phase=teardown). */
    public const TRIGGER_BEFORE_TEARDOWN = 'before_teardown';

    public static function triggers(): array
    {
        return [
            self::TRIGGER_AFTER_PROVISION,
            self::TRIGGER_WHEN_READY,
            self::TRIGGER_MANUAL,
            self::TRIGGER_BEFORE_TEARDOWN,
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function providerConfiguration(): BelongsTo
    {
        return $this->belongsTo(EnvironmentTemplateProviderConfiguration::class, 'provider_configuration_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AnsiblePlaybookRun::class, 'playbook_id');
    }

    /**
     * Granular task steps for UI progress display.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(AnsiblePlaybookTask::class, 'ansible_playbook_id')->orderBy('position');
    }

    /**
     * Activities that THIS activity depends on (must complete before this one starts).
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            AnsiblePlaybook::class,
            'ansible_playbook_dependencies',
            'playbook_id',
            'depends_on_playbook_id',
        );
    }

    /**
     * Activities that depend on THIS activity (this must complete before they start).
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            AnsiblePlaybook::class,
            'ansible_playbook_dependencies',
            'depends_on_playbook_id',
            'playbook_id',
        );
    }
}
