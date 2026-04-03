<?php

namespace App\Http\Requests;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'owner_id'    => ['required', 'integer', 'exists:team_members,id'],
            'status'      => ['sometimes', Rule::in(array_column(ProjectStatus::cases(), 'value'))],
            'priority'    => ['sometimes', Rule::in(array_column(ProjectPriority::cases(), 'value'))],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'member_ids'  => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:team_members,id'],
        ];
    }
}
