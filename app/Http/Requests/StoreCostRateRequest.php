<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCostRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_member_id'         => ['required', 'exists:team_members,id'],
            'monthly_ctc'            => ['required', 'numeric', 'min:0'],
            'working_hours_per_month' => ['nullable', 'integer', 'min:1', 'max:300'],
            'overhead_multiplier'    => ['nullable', 'numeric', 'min:0.5', 'max:3.0'],
            'effective_from'         => ['required', 'date'],
            'effective_to'           => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
