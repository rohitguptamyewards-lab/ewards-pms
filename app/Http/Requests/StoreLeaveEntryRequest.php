<?php

namespace App\Http\Requests;

use App\Enums\LeaveSource;
use App\Enums\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_member_id' => ['required', 'exists:team_members,id'],
            'leave_date'     => ['required', 'date'],
            'leave_type'     => ['required', Rule::in(array_column(LeaveType::cases(), 'value'))],
            'half_day'       => ['boolean'],
            'source'         => ['nullable', Rule::in(array_column(LeaveSource::cases(), 'value'))],
            'hrms_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
