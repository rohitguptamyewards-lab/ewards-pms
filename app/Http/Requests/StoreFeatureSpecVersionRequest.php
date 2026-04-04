<?php

namespace App\Http\Requests;

use App\Enums\SpecChangeType;
use App\Enums\SpecVersionState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeatureSpecVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feature_id'     => ['required', 'integer', 'exists:features,id'],
            'content'        => ['required', 'string'],
            'change_summary' => ['nullable', 'string', 'max:1000'],
            'state'          => ['nullable', 'string', Rule::in(array_column(SpecVersionState::cases(), 'value'))],
            'change_type'    => ['nullable', 'string', Rule::in(array_column(SpecChangeType::cases(), 'value'))],
        ];
    }
}
