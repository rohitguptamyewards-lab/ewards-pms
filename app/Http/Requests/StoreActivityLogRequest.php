<?php

namespace App\Http\Requests;

use App\Enums\ActivityStatus;
use App\Enums\ActivityTypeDeveloper;
use App\Enums\ActivityTypeTester;
use App\Enums\ActivityTypeAnalyst;
use App\Enums\AiCapability;
use App\Enums\AiContribution;
use App\Enums\AiOutcome;
use App\Enums\AiTimeSaved;
use App\Enums\DurationEnum;
use App\Enums\EffortConfidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Combine all role-specific activity types
        $allActivityTypes = array_merge(
            array_column(ActivityTypeDeveloper::cases(), 'value'),
            array_column(ActivityTypeTester::cases(), 'value'),
            array_column(ActivityTypeAnalyst::cases(), 'value'),
        );

        return [
            'activity_type'     => ['required', 'string', Rule::in($allActivityTypes)],
            'feature_id'        => ['nullable', 'integer', 'exists:features,id'],
            'duration'          => ['required', 'string', Rule::in(array_column(DurationEnum::cases(), 'value'))],
            'effort_confidence' => ['nullable', 'string', Rule::in(array_column(EffortConfidence::cases(), 'value'))],
            'status'            => ['required', 'string', Rule::in(array_column(ActivityStatus::cases(), 'value'))],
            'blocker_reason'    => ['nullable', 'string'],
            'note'              => ['nullable', 'string'],
            'log_date'          => ['required', 'date'],
            'ai_used'           => ['nullable', 'boolean'],
            'ai_tool_id'        => ['nullable', 'integer'],
            'ai_capability'     => ['nullable', 'string', Rule::in(array_column(AiCapability::cases(), 'value'))],
            'ai_contribution'   => ['nullable', 'string', Rule::in(array_column(AiContribution::cases(), 'value'))],
            'ai_outcome'        => ['nullable', 'string', Rule::in(array_column(AiOutcome::cases(), 'value'))],
            'ai_time_saved'     => ['nullable', 'string', Rule::in(array_column(AiTimeSaved::cases(), 'value'))],
            'ai_note'           => ['nullable', 'string'],
            'cost_rate_id'      => ['nullable', 'integer', 'exists:cost_rates,id'],
            'is_same_as_yesterday' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            if ($validator->errors()->any()) {
                return;
            }

            if ($this->input('status') === 'blocked' && empty($this->input('blocker_reason'))) {
                $validator->errors()->add('blocker_reason', 'Blocker reason is required when status is blocked.');
            }
        });
    }
}
