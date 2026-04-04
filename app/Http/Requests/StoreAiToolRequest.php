<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAiToolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'provider'              => ['required', 'string', 'max:255'],
            'capabilities'          => ['nullable', 'array'],
            'capabilities.*'        => ['string'],
            'cost_per_seat_monthly' => ['required', 'numeric', 'min:0'],
            'seats_purchased'       => ['required', 'integer', 'min:0'],
            'is_active'             => ['boolean'],
        ];
    }
}
