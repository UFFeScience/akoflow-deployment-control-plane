<?php

namespace App\Http\Requests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

trait FailedValidationTrait
{
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->all();
        throw new HttpResponseException(response()->json(['errors' => $errors], 422));
    }
}