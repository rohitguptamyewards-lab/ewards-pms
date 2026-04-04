<?php

namespace App\Http\Requests;

use App\Enums\AssignmentRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeatureAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feature_id'      => ['required', 'exists:features,id'],
            'team_member_id'  => ['required', 'exists:team_members,id'],
            'role'            => ['required', Rule::in(array_column(AssignmentRole::cases(), 'value'))],
            'estimated_hours' => ['required', 'integer', 'min:1'],
        ];
    }
}
