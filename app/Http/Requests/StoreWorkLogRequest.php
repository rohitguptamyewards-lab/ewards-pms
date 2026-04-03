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
            'hours_spent' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'status'      => ['required', Rule::in(array_column(WorkLogStatus::cases(), 'value'))],
            'note'        => ['nullable', 'string'],
            'blocker'     => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * If the related task has status=blocked, then the blocker field is required.
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
