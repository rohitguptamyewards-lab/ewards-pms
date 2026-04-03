<?php

namespace App\Http\Requests;

use App\Enums\RequestType;
use App\Enums\RequestUrgency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequestFormRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'merchant_id' => ['required', 'integer', 'exists:merchants,id'],
            'type' => ['required', Rule::in(array_column(RequestType::cases(), 'value'))],
            'urgency' => ['required', Rule::in(array_column(RequestUrgency::cases(), 'value'))],
        ];
    }
}
