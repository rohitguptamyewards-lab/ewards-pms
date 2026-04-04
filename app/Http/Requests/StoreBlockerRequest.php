<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feature_id'  => ['required', 'integer', 'exists:features,id'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
