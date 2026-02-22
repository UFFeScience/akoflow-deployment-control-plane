<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
