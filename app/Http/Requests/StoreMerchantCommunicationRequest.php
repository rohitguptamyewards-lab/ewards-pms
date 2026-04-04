<?php

namespace App\Http\Requests;

use App\Enums\CommunicationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMerchantCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_id'     => ['required', 'integer', 'exists:merchants,id'],
            'feature_id'      => ['nullable', 'integer', 'exists:features,id'],
            'channel'         => ['required', 'string', Rule::in(array_column(CommunicationChannel::cases(), 'value'))],
            'summary'         => ['required', 'string', 'max:5000'],
            'commitment_made' => ['nullable', 'boolean'],
            'commitment_date' => ['nullable', 'date'],
        ];
    }
}
