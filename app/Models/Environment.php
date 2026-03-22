<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 */
class Environment extends Model
{
	protected $table = 'environments';
	protected $fillable = [
		'project_id',
		'environment_template_version_id',
		'name',
		'description',
		'status',
		'execution_mode',
		'configuration_json',
	];

	public const STATUSES = ['PENDING', 'RUNNING', 'STOPPED', 'FAILED', 'COMPLETED'];

	protected $casts = [
		'configuration_json' => 'array',
	];

	public function deployments(): HasMany
	{
		return $this->hasMany(Deployment::class, 'environment_id');
	}

	public function project(): BelongsTo
	{
		return $this->belongsTo(Project::class, 'project_id');
	}

	public function templateVersion(): BelongsTo
	{
		return $this->belongsTo(EnvironmentTemplateVersion::class, 'environment_template_version_id');
	}
}
