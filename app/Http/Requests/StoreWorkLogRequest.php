<?php

namespace App\Http\Requests;

use App\Enums\WorkLogStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkLogRequest extends FormRequest
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
            'project_id'  => ['required', 'integer', 'exists:projects,id'],
            'task_id'     => ['nullable', 'integer', 'exists:tasks,id'],
            'log_date'    => ['required', 'date'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'status'      => ['required', Rule::in(array_column(WorkLogStatus::cases(), 'value'))],
            'note'        => ['nullable', 'string'],
            'blocker'     => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'End time must be after start time.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $task = Task::find($this->input('task_id'));

            if ($task && $task->status->value === 'blocked' && empty($this->input('blocker'))) {
                $validator->errors()->add(
                    'blocker',
                    'The blocker field is required when the task status is blocked.'
                );
            }
        });
    }
}
