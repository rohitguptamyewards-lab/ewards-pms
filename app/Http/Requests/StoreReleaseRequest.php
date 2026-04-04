<?php

namespace App\Http\Requests;

use App\Enums\ReleaseEnvironment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version'      => ['required', 'string', 'max:50'],
            'release_date' => ['required', 'date'],
            'environment'  => ['required', 'string', Rule::in(array_column(ReleaseEnvironment::cases(), 'value'))],
            'notes'        => ['nullable', 'string'],
            'feature_ids'  => ['nullable', 'array'],
            'feature_ids.*'=> ['integer', 'exists:features,id'],
        ];
    }
}
