<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderVariableSchema extends Model
{
    protected $table = 'provider_variable_schemas';

    protected $fillable = [
        'provider_slug',
        'section',
        'name',
        'label',
        'description',
        'type',
        'required',
        'is_sensitive',
        'position',
        'options_json',
        'default_value',
    ];

    public const TYPES = ['string', 'select', 'secret', 'boolean', 'textarea', 'number'];

    protected $casts = [
        'required'     => 'boolean',
        'is_sensitive' => 'boolean',
        'position'     => 'integer',
    ];

    public function getOptionsAttribute(): ?array
    {
        return $this->options_json ? json_decode($this->options_json, true) : null;
    }
}
