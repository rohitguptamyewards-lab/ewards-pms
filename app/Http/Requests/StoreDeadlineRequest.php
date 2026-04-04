<?php

namespace App\Http\Requests;

use App\Enums\DeadlineType;
use App\Enums\DeadlineState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deadlineable_type' => ['required', 'string', Rule::in(['feature', 'initiative'])],
            'deadlineable_id'   => ['required', 'integer'],
            'type'              => ['required', 'string', Rule::in(array_column(DeadlineType::cases(), 'value'))],
            'state'             => ['nullable', 'string', Rule::in(array_column(DeadlineState::cases(), 'value'))],
            'due_date'          => ['required', 'date'],
            'cascade_from_id'   => ['nullable', 'integer', 'exists:deadlines,id'],
        ];
    }
}
