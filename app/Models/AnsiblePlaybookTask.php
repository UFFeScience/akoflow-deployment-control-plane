<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnsiblePlaybookTask extends Model
{
    protected $table = 'ansible_playbook_tasks';

    protected $fillable = [
        'ansible_playbook_id',
        'position',
        'name',
        'module',
        'module_args_json',
        'when_condition',
        'become',
        'tags_json',
        'enabled',
    ];

    protected $casts = [
        'module_args_json' => 'array',
        'tags_json'        => 'array',
        'become'           => 'boolean',
        'enabled'          => 'boolean',
        'position'         => 'integer',
    ];

    public function ansiblePlaybook(): BelongsTo
    {
        return $this->belongsTo(AnsiblePlaybook::class, 'ansible_playbook_id');
    }
}
