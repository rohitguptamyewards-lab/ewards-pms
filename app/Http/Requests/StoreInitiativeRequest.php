<?php

namespace App\Http\Requests;

use App\Enums\ExpectedImpact;
use App\Enums\InitiativeOriginType;
use App\Enums\InitiativeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInitiativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:255'],
            'description'       => ['required', 'string'],
            'origin_type'       => ['required', Rule::in(array_column(InitiativeOriginType::cases(), 'value'))],
            'business_case'     => ['required', 'string'],
            'expected_impact'   => ['required', Rule::in(array_column(ExpectedImpact::cases(), 'value'))],
            'owner_id'          => ['required', 'exists:team_members,id'],
            'deadline'          => ['nullable', 'date'],
            'status'            => ['nullable', Rule::in(array_column(InitiativeStatus::cases(), 'value'))],
            'estimated_features' => ['nullable', 'integer', 'min:0'],
            'module_id'         => ['nullable', 'exists:modules,id'],
        ];
    }
}
