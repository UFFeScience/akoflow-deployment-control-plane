<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experiment extends Model
{
	protected $table = 'experiments';
	protected $fillable = [
		'project_id',
		'experiment_template_version_id',
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

	public function clusters(): HasMany
	{
		return $this->hasMany(Cluster::class, 'experiment_id');
	}

	public function project(): BelongsTo
	{
		return $this->belongsTo(Project::class, 'project_id');
	}

	public function templateVersion(): BelongsTo
	{
		return $this->belongsTo(ExperimentTemplateVersion::class, 'experiment_template_version_id');
	}
}
