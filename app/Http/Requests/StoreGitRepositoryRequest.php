<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGitRepositoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'git_provider_id' => ['required', 'integer', 'exists:git_providers,id'],
            'name'            => ['required', 'string', 'max:255'],
            'url'             => ['required', 'string', 'max:500'],
            'default_branch'  => ['nullable', 'string', 'max:100'],
            'webhook_secret'  => ['nullable', 'string'],
            'is_active'       => ['nullable', 'boolean'],
        ];
    }
}
