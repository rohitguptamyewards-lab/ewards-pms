<?php

namespace App\Http\Requests;

use App\Enums\AiCapability;
use App\Enums\AiContribution;
use App\Enums\AiOutcome;
use App\Enums\AiTimeSaved;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiUsageLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_log_id'    => ['required', 'exists:activity_logs,id'],
            'ai_tool_id'        => ['required', 'exists:ai_tools,id'],
            'capability'         => ['required', Rule::in(array_column(AiCapability::cases(), 'value'))],
            'contribution'       => ['required', Rule::in(array_column(AiContribution::cases(), 'value'))],
            'outcome'            => ['required', Rule::in(array_column(AiOutcome::cases(), 'value'))],
            'time_saved'         => ['required', Rule::in(array_column(AiTimeSaved::cases(), 'value'))],
            'note'               => ['nullable', 'string', 'max:2000'],
            'prompt_template_id' => ['nullable', 'exists:prompt_templates,id'],
        ];
    }
}
