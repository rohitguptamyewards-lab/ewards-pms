<?php

namespace App\Http\Requests;

use App\Enums\AiCapability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromptTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ai_tool_id'  => ['required', 'exists:ai_tools,id'],
            'capability'  => ['required', Rule::in(array_column(AiCapability::cases(), 'value'))],
            'title'       => ['required', 'string', 'max:255'],
            'content'     => ['required', 'string'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:50'],
        ];
    }
}
