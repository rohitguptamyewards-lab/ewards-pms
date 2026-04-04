<?php

namespace App\Http\Requests;

use App\Enums\BugOrigin;
use App\Enums\BugRootCause;
use App\Enums\BugSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBugSlaRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feature_id' => ['required', 'integer', 'exists:features,id'],
            'severity'   => ['required', 'string', Rule::in(array_column(BugSeverity::cases(), 'value'))],
            'root_cause' => ['nullable', 'string', Rule::in(array_column(BugRootCause::cases(), 'value'))],
            'origin'     => ['nullable', 'string', Rule::in(array_column(BugOrigin::cases(), 'value'))],
        ];
    }
}
