<?php

namespace App\Http\Requests;

use App\Enums\DecisionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'              => ['required', 'string', 'max:255'],
            'context'            => ['required', 'string'],
            'options_considered' => ['required', 'string'],
            'chosen_option'      => ['required', 'string'],
            'rationale'          => ['required', 'string'],
            'decision_maker_id'  => ['required', 'exists:team_members,id'],
            'decision_date'      => ['required', 'date'],
            'linked_to_type'     => ['nullable', Rule::in(['feature', 'initiative', 'module'])],
            'linked_to_id'       => ['nullable', 'integer'],
            'status'             => ['nullable', Rule::in(array_column(DecisionStatus::cases(), 'value'))],
        ];
    }
}
