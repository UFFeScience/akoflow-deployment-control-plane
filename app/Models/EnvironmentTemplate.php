<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 */
class EnvironmentTemplate extends Model
{
	protected $table = 'environment_templates';
	protected $fillable = ['name','slug','description','is_public','owner_organization_id'];

	protected $casts = [
		'is_public' => 'boolean',
	];

	public function versions(): HasMany
	{
		return $this->hasMany(EnvironmentTemplateVersion::class, 'template_id');
	}

	public function ownerOrganization(): BelongsTo
	{
		return $this->belongsTo(Organization::class, 'owner_organization_id');
	}
}
