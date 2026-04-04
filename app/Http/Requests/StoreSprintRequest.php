<?php

namespace App\Http\Requests;

use App\Enums\SprintStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'goal'                    => ['nullable', 'string', 'max:1000'],
            'start_date'              => ['required', 'date'],
            'end_date'                => ['required', 'date', 'after:start_date'],
            'total_capacity_hours'    => ['nullable', 'integer', 'min:0'],
            'capacity_override_reason'=> ['nullable', 'string'],
            'status'                  => ['nullable', 'string', Rule::in(array_column(SprintStatus::cases(), 'value'))],
        ];
    }
}
