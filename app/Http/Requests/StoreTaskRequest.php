<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Inject projectId from the route if not already in the request body
        if ($this->route('projectId') && !$this->has('project_id')) {
            $this->merge(['project_id' => (int) $this->route('projectId')]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:team_members,id'],
            'status' => ['sometimes', Rule::in(array_column(TaskStatus::cases(), 'value'))],
            'priority' => ['sometimes', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'deadline' => ['nullable', 'date'],
        ];
    }
}
