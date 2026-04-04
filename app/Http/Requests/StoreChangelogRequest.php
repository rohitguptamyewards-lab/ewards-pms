<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChangelogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'              => ['required', 'string', 'max:255'],
            'body'               => ['nullable', 'string'],
            'release_id'         => ['nullable', 'exists:releases,id'],
            'audience_module_ids' => ['nullable', 'array'],
            'audience_module_ids.*' => ['integer', 'exists:modules,id'],
        ];
    }
}
